#!/usr/bin/env python3
"""Generate the OpenAPI document from Laravel routes and Postman examples."""

import json
import re
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
COLLECTIONS = ROOT.parent / "postman_collections"
OUTPUT = ROOT / "public" / "openapi.json"
REQUESTS = ROOT / "app" / "Http" / "Requests"
CONTROLLERS = ROOT / "app" / "Http" / "Controllers"


def normalize_path(path):
    path = re.sub(r":([A-Za-z_][A-Za-z0-9_]*)", r"{\1}", path)
    path = re.sub(r"\{([^}?]+)\?\}", r"{\1}", path)
    return "/" + path.strip("/")


def php_block(source, opening):
    """Return a balanced PHP block while ignoring quoted text and comments."""
    pairs = {"{": "}", "[": "]", "(": ")"}
    if opening >= len(source) or source[opening] not in pairs:
        return ""
    stack = []
    quote = None
    line_comment = False
    block_comment = False
    escaped = False

    for index in range(opening, len(source)):
        char = source[index]
        following = source[index + 1] if index + 1 < len(source) else ""
        if line_comment:
            if char == "\n":
                line_comment = False
            continue
        if block_comment:
            if char == "*" and following == "/":
                block_comment = False
            continue
        if quote:
            if escaped:
                escaped = False
            elif char == "\\":
                escaped = True
            elif char == quote:
                quote = None
            continue
        if char == "/" and following == "/":
            line_comment = True
            continue
        if char == "#" and following != "[":
            line_comment = True
            continue
        if char == "/" and following == "*":
            block_comment = True
            continue
        if char in ("'", '"', "`"):
            quote = char
            continue
        if char in pairs:
            stack.append(pairs[char])
        elif stack and char == stack[-1]:
            stack.pop()
            if not stack:
                return source[opening : index + 1]
    return ""


def split_php_arguments(value):
    pieces = []
    start = 0
    stack = []
    quote = None
    escaped = False
    pairs = {"(": ")", "[": "]", "{": "}"}
    for index, char in enumerate(value):
        if quote:
            if escaped:
                escaped = False
            elif char == "\\":
                escaped = True
            elif char == quote:
                quote = None
            continue
        if char in ("'", '"', "`"):
            quote = char
        elif char in pairs:
            stack.append(pairs[char])
        elif stack and char == stack[-1]:
            stack.pop()
        elif char == "," and not stack:
            pieces.append(value[start:index].strip())
            start = index + 1
    remainder = value[start:].strip()
    if remainder:
        pieces.append(remainder)
    return pieces


def php_array_entries(expression):
    expression = expression.strip()
    if not expression.startswith("["):
        return None
    block = php_block(expression, 0)
    if not block:
        return None
    entries = []
    for part in split_php_arguments(block[1:-1]):
        arrow = find_top_level_arrow(part)
        if arrow < 0:
            entries.append((None, part.strip()))
        else:
            key = part[:arrow].strip().strip("'\"")
            entries.append((key, part[arrow + 2 :].strip()))
    return entries


def find_top_level_arrow(value):
    stack = []
    quote = None
    escaped = False
    for index, char in enumerate(value[:-1]):
        if quote:
            if escaped:
                escaped = False
            elif char == "\\":
                escaped = True
            elif char == quote:
                quote = None
            continue
        if char in ("'", '"', "`"):
            quote = char
        elif char in "([{":
            stack.append({"(": ")", "[": "]", "{": "}"}[char])
        elif stack and char == stack[-1]:
            stack.pop()
        elif char == "=" and value[index + 1] == ">" and not stack:
            return index
    return -1


def php_expression_schema(expression, field_name=None):
    value = expression.strip()
    if not value:
        return {"type": "object", "additionalProperties": True}
    field = (field_name or "").lower()
    if field in ("id", "user_id", "area_id", "rw_id", "schedule_id", "service_account_id"):
        return {"type": "integer"}
    if field.endswith(("_id", "_count", "_quantity")) or field in (
        "total",
        "count",
        "quantity",
    ):
        return {"type": "integer", "format": "int64"}
    if field in ("amount", "price", "latitude", "longitude", "distance", "weight"):
        return {"type": "number", "format": "double"}
    if field in (
        "name",
        "email",
        "token",
        "token_type",
        "status",
        "message",
        "address",
        "area",
        "rw",
        "category",
        "phone",
        "phone_number",
        "resident_name",
        "account_number",
        "type",
        "code",
    ) or field.endswith(("_name", "_status", "_url", "_path", "_note")):
        return {"type": "string"}
    if field.endswith(("_at", "_date", "_time")):
        return {
            "type": "string",
            "format": "date-time" if field.endswith("_at") else "date",
        }
    if field in ("items", "roles", "photos", "waste_types", "history"):
        return {"type": "array", "items": {"type": "object", "additionalProperties": True}}
    if value.startswith("["):
        entries = php_array_entries(value)
        if entries is not None:
            named = [(key, child) for key, child in entries if key is not None]
            if named:
                properties = {
                    key: php_expression_schema(child, key) for key, child in named
                }
                for key, schema in properties.items():
                    schema.setdefault("description", describe_field(key))
                    add_schema_descriptions(schema)
                return {
                    "type": "object",
                    "required": [key for key, _ in named],
                    "properties": properties,
                    "additionalProperties": True,
                }
            return {
                "type": "array",
                "items": (
                    php_expression_schema(entries[0][1])
                    if entries
                    else {"type": "object", "additionalProperties": True}
                ),
            }
    if value in ("true", "false"):
        return {"type": "boolean"}
    if value == "null":
        return {"nullable": True}
    if re.fullmatch(r"-?\d+", value):
        return {"type": "integer"}
    if re.fullmatch(r"-?\d+\.\d+", value):
        return {"type": "number"}
    if value.startswith(("'", '"')):
        return {"type": "string"}
    if "->map(" in value or "->values()" in value or "->all()" in value:
        return {"type": "array", "items": {"type": "object"}}
    if "->count()" in value or re.search(r"->(?:currentPage|perPage|total|lastPage)\(", value):
        return {"type": "integer"}
    if "->format(" in value or "->toDateString(" in value:
        return {"type": "string", "format": "date"}
    return {"type": "object", "additionalProperties": True}


def php_methods(source):
    methods = {}
    pattern = re.compile(r"(?P<doc>/\*\*.*?\*/\s*)?(?:public|protected|private)\s+function\s+(?P<name>\w+)\s*\(", re.S)
    for match in pattern.finditer(source):
        open_paren = source.find("(", match.start())
        params = php_block(source, open_paren)
        if not params:
            continue
        close_paren = open_paren + len(params) - 1
        body_open = source.find("{", close_paren)
        if body_open < 0:
            continue
        body = php_block(source, body_open)
        if body:
            methods[match.group("name")] = {
                "body": body[1:-1],
                "params": source[open_paren + 1 : close_paren],
                "doc": match.group("doc") or "",
            }
    return methods


def clean_docblock(doc):
    lines = []
    for line in doc.splitlines():
        line = re.sub(r"^\s*/?\*+\s?", "", line).strip()
        if line and not line.startswith("@") and not line.startswith("/api/"):
            lines.append(line)
    return "\n".join(lines)


def controller_action(action, cache):
    class_name, separator, method_name = action.partition("@")
    if not separator:
        return None
    prefix = "App\\Http\\Controllers\\"
    if not class_name.startswith(prefix):
        return None
    relative_name = class_name[len(prefix) :]
    relative = Path(*relative_name.split("\\")).with_suffix(".php")
    path = CONTROLLERS / relative
    if not path.exists():
        return None
    key = str(path)
    if key not in cache:
        cache[key] = {
            "source": path.read_text(encoding="utf-8"),
            "methods": php_methods(path.read_text(encoding="utf-8")),
        }
    entry = cache[key]
    method = entry["methods"].get(method_name)
    if method:
        method["child_source"] = entry["source"]
        method["controller_class"] = relative_name.rsplit("\\", 1)[-1]
        return method

    parent_match = re.search(
        r"\bclass\s+\w+\s+extends\s+([A-Za-z_]\w*)", entry["source"]
    )
    if not parent_match:
        return None
    parent_path = path.parent / f"{parent_match.group(1)}.php"
    if not parent_path.exists():
        return None
    parent_key = str(parent_path)
    if parent_key not in cache:
        parent_source = parent_path.read_text(encoding="utf-8")
        cache[parent_key] = {
            "source": parent_source,
            "methods": php_methods(parent_source),
        }
    method = cache[parent_key]["methods"].get(method_name)
    if method:
        method["child_source"] = entry["source"]
        method["controller_class"] = relative_name.rsplit("\\", 1)[-1]
        method["inherited_from"] = parent_match.group(1)
        return method
    return None


def validation_rules(action_info):
    if not action_info:
        return {}
    body = action_info["body"]
    request_classes = re.findall(r"\b([A-Z]\w*Request)\s+\$\w+", action_info["params"])
    sources = []
    for match in re.finditer(r"->validate\s*\(", body):
        opening = body.find("(", match.start())
        call = php_block(body, opening)
        if call:
            array_start = call.find("[")
            rules_block = php_block(call, array_start)
            if rules_block:
                sources.append(rules_block)
    for arguments in php_call_arguments(body, r"Validator::make\s*\("):
        if len(arguments) > 1 and arguments[1].startswith("["):
            sources.append(arguments[1])
    if request_classes:
        request_file = REQUESTS / f"{request_classes[0]}.php"
        if request_file.exists():
            request_methods = php_methods(request_file.read_text(encoding="utf-8"))
            rules_method = request_methods.get("rules")
            if rules_method:
                rules_source = rules_method["body"]
                for match in re.finditer(r"\breturn\s*\[", rules_source):
                    array_start = rules_source.find("[", match.start())
                    rules_block = php_block(rules_source, array_start)
                    if rules_block:
                        sources.append(rules_block)
    child_source = action_info.get("child_source", "")
    configured_rules = re.search(
        r"\$this->(rulesStore|rulesUpdate)\b", body
    )
    if child_source and configured_rules:
        property_name = configured_rules.group(1)
        property_match = re.search(
            rf"\${property_name}\s*=\s*\[", child_source
        )
        if property_match:
            array_start = child_source.find("[", property_match.start())
            rules_block = php_block(child_source, array_start)
            if rules_block:
                sources.append(rules_block)

    result = {}
    for source in sources:
        for match in re.finditer(r"""['"]([^'"]+)['"]\s*=>\s*(\[[^\]]*\]|['"][^'"]*['"])""", source):
            name, raw_rules = match.groups()
            parsed = re.findall(r"""['"]([^'"]+)['"]""", raw_rules)
            if not parsed and raw_rules[0] in ("'", '"'):
                parsed = [raw_rules[1:-1]]
            rules = []
            for rule in parsed:
                rules.extend(part.strip() for part in rule.split("|") if part.strip())
            result[name] = rules
    return result


def rule_schema(name, rules):
    schema = {"type": "string"}
    for rule in rules:
        kind, _, argument = rule.partition(":")
        if kind in ("integer", "int"):
            schema = {"type": "integer"}
        elif kind in ("numeric", "decimal"):
            schema = {"type": "number"}
        elif kind in ("boolean", "accepted", "declined"):
            schema = {"type": "boolean"}
        elif kind == "array":
            schema = {"type": "array", "items": {"type": "string"}}
        elif kind in ("email",):
            schema.update({"type": "string", "format": "email"})
        elif kind in ("url", "active_url"):
            schema.update({"type": "string", "format": "uri"})
        elif kind in ("date",):
            schema.update({"type": "string", "format": "date"})
        elif kind in ("date_format",):
            schema.update({"type": "string", "format": "date"})
        elif kind in ("uuid",):
            schema.update({"type": "string", "format": "uuid"})
        elif kind in ("image", "file"):
            schema.update({"type": "string", "format": "binary"})
        elif kind == "in":
            schema["enum"] = argument.split(",")
        elif kind == "exists" and name.endswith("_id"):
            schema = {"type": "integer"}
        elif kind == "between":
            lower, separator, upper = argument.partition(",")
            if separator:
                try:
                    schema["minimum"] = float(lower)
                    schema["maximum"] = float(upper)
                except ValueError:
                    pass
        elif kind in ("min", "max", "size") and argument.isdigit():
            constraint = int(argument)
            if schema.get("type") == "integer" or schema.get("type") == "number":
                schema["minimum" if kind == "min" else "maximum"] = constraint
            elif schema.get("type") == "array":
                schema["minItems" if kind == "min" else "maxItems"] = constraint
            elif kind in ("min", "max"):
                schema["minLength" if kind == "min" else "maxLength"] = constraint
        elif kind in ("nullable", "sometimes", "required", "present", "filled"):
            if kind == "nullable":
                schema["nullable"] = True
    schema["description"] = describe_field(name, rules)
    return schema


def describe_field(name, rules=()):
    known = {
        "id": "Unique resource identifier.",
        "email": "Email address.",
        "password": "Account password.",
        "password_confirmation": "Must match the password field.",
        "page": "Page number for paginated results.",
        "page.size": "Number of results to include on each page.",
        "per_page": "Maximum number of results per page.",
        "status": "Resource status.",
        "search": "Search term used to filter results.",
        "q": "Search term used to filter results.",
        "sort": "Field used to sort results.",
        "direction": "Sort direction.",
        "latitude": "Latitude coordinate.",
        "longitude": "Longitude coordinate.",
        "phone_number": "Contact phone number.",
        "contact_phone": "Contact phone number.",
    }
    if name in known:
        description = known[name]
    else:
        description = name.replace("_", " ").replace(".", " ").capitalize() + "."
    if "required" in rules:
        description += " Required."
    for rule in rules:
        if rule.startswith("exists:"):
            description += " Must reference an existing record."
            break
    return description


def add_schema_descriptions(schema):
    if schema.get("type") == "object":
        for name, child in schema.get("properties", {}).items():
            child.setdefault("description", describe_field(name))
            add_schema_descriptions(child)
    elif schema.get("type") == "array" and isinstance(schema.get("items"), dict):
        add_schema_descriptions(schema["items"])


def nested_request_schema(fields):
    properties = {}
    required = set()
    for name, rules in fields.items():
        if name.endswith(".*"):
            continue
        segments = name.split(".")
        target = properties
        for segment in segments[:-1]:
            existing = target.setdefault(segment, {"type": "object", "properties": {}})
            target = existing.setdefault("properties", {})
        target[segments[-1]] = rule_schema(name, rules)
        if "required" in rules and "sometimes" not in rules and len(segments) == 1:
            required.add(name)
    for name, rules in fields.items():
        if name.endswith(".*"):
            array_name = name[:-2]
            if array_name in properties and properties[array_name].get("type") == "array":
                properties[array_name]["items"] = rule_schema(name, rules)
    schema = {"type": "object", "properties": properties, "additionalProperties": True}
    if required:
        schema["required"] = sorted(required)
    return schema


def collection_operations():
    operations = {}

    def walk(items, parents):
        for item in items:
            request = item.get("request")
            if request:
                url = request.get("url", {})
                path = "/".join(url.get("path", []))
                if not path and isinstance(url.get("raw"), str):
                    path = url["raw"].split("?", 1)[0]
                path = re.sub(r"^\{\{[^}]+\}\}/", "", path)
                key = (request.get("method", "GET").lower(), normalize_path(path))
                operations.setdefault(key, []).append(
                    {
                        "name": item.get("name"),
                        "description": item.get("description"),
                        "request": request,
                        "folder": " / ".join(parents),
                    }
                )
            walk(item.get("item", []), parents + [item.get("name", "")])

    for collection in sorted(COLLECTIONS.glob("*.postman_collection.json")):
        with collection.open(encoding="utf-8-sig") as file:
            data = json.load(file)
        walk(data.get("item", []), [])

    return operations


def schema_for(value):
    if isinstance(value, bool):
        return {"type": "boolean", "example": value}
    if isinstance(value, int):
        return {"type": "integer", "example": value}
    if isinstance(value, float):
        return {"type": "number", "example": value}
    if value is None:
        return {"nullable": True, "example": None}
    if isinstance(value, list):
        schema = {"type": "array", "items": {}, "example": value}
        if value:
            schema["items"] = schema_for(value[0])
        return schema
    if isinstance(value, dict):
        return {
            "type": "object",
            "properties": {key: schema_for(child) for key, child in value.items()},
            "additionalProperties": True,
            "example": value,
        }
    return {"type": "string", "example": value}


def example_body(examples):
    for example in examples:
        body = example["request"].get("body", {})
        mode = body.get("mode")
        if mode == "raw":
            raw = body.get("raw", "")
            try:
                sample = json.loads(raw)
            except (TypeError, json.JSONDecodeError):
                continue
            return {
                "media_type": "application/json",
                "schema": schema_for(sample),
                "example": sample,
            }
        if mode == "formdata":
            fields = [
                field
                for field in body.get("formdata", [])
                if field.get("key") and field.get("type") != "file"
            ]
            properties = {
                field["key"]: {"type": "string", "example": field.get("value", "")}
                for field in fields
            }
            for field in body.get("formdata", []):
                if field.get("key") and field.get("type") == "file":
                    properties[field["key"]] = {"type": "string", "format": "binary"}
            return {
                "media_type": "multipart/form-data",
                "schema": {"type": "object", "properties": properties},
                "example": {
                    key: value.get("example", "")
                    for key, value in properties.items()
                    if value.get("format") != "binary"
                },
            }
    return None


def merge_request_schema(example_schema, rules):
    schema = nested_request_schema(rules)
    if example_schema and example_schema.get("type") == "object":
        properties = schema.setdefault("properties", {})
        for name, example_property in example_schema.get("properties", {}).items():
            if name not in properties:
                properties[name] = example_property
                properties[name].setdefault("description", describe_field(name))
                add_schema_descriptions(properties[name])
            else:
                if "example" in example_property:
                    properties[name].setdefault("example", example_property["example"])
                for key in ("type", "format", "enum"):
                    if key not in properties[name] and key in example_property:
                        properties[name][key] = example_property[key]
                add_schema_descriptions(properties[name])
    return schema


def request_body(examples, rules, schema_name):
    sample = example_body(examples)
    file_upload = any(
        rule in ("file", "image") for field_rules in rules.values() for rule in field_rules
    )
    if sample:
        media_type = sample["media_type"]
    else:
        media_type = "multipart/form-data" if file_upload else "application/json"
    if file_upload:
        media_type = "multipart/form-data"
    example_schema = sample.get("schema") if sample else None
    schema = merge_request_schema(example_schema, rules)
    if not schema.get("properties"):
        return None
    schema.setdefault("description", "Request payload.")

    content = {"schema": {"$ref": f"#/components/schemas/{schema_name}"}}
    if sample and sample.get("example") is not None:
        content["example"] = sample["example"]
    return {
        "required": bool(schema.get("required")) or sample is not None,
        "content": {media_type: content},
    }, schema


def php_call_arguments(source, pattern):
    result = []
    for match in re.finditer(pattern, source):
        opening = match.end() - 1
        call = php_block(source, opening)
        if call:
            result.append(split_php_arguments(call[1:-1]))
    return result


def infer_assigned_array(source, variable):
    match = re.search(rf"\${re.escape(variable)}\s*=\s*(\[)", source)
    if match:
        array = php_block(source, match.start(1))
        if array:
            return php_expression_schema(array)

    map_match = re.search(
        rf"\${re.escape(variable)}\s*=.*?->map\s*\(\s*function\b",
        source,
        re.S,
    )
    if not map_match:
        return None
    closure_open = source.find("{", map_match.end())
    closure = php_block(source, closure_open)
    if not closure:
        return None
    return_match = re.search(r"\breturn\s*\[", closure)
    if not return_match:
        return None
    array_start = closure.find("[", return_match.start())
    array = php_block(closure, array_start)
    if not array:
        return None
    return {
        "type": "array",
        "items": php_expression_schema(array),
    }


def infer_response_variables(schema, expression, source):
    if schema.get("type") != "object":
        return schema
    entries = php_array_entries(expression)
    if not entries:
        return schema
    properties = schema.get("properties", {})
    for key, value in entries:
        if key not in properties:
            continue
        variable = re.fullmatch(r"\$(\w+)", value.strip())
        if variable:
            inferred = infer_assigned_array(source, variable.group(1))
            if inferred:
                properties[key] = inferred
                add_schema_descriptions(inferred)
    return schema


def model_response_schema(action_info, expression):
    child_source = action_info.get("child_source", "")
    model_match = re.search(r"\$modelClass\s*=\s*(\w+)::class", child_source)
    if model_match:
        model_name = model_match.group(1)
    else:
        query_match = re.search(r"\b(\w+)::query\s*\(", child_source)
        variable_match = re.fullmatch(r"\$\w+", expression.strip())
        variable_name = variable_match.group(0)[1:] if variable_match else None
        model_name = query_match.group(1) if query_match else None
        if model_name is None and variable_name:
            for imported in re.findall(
                r"^\s*use\s+App\\Models\\(\w+)\s*;", child_source, re.M
            ):
                if imported.lower() == variable_name.lower():
                    model_name = imported
                    break
        if model_name is None:
            return None

    model_path = ROOT / "app" / "Models" / f"{model_name}.php"
    if not model_path.exists():
        return None
    source = model_path.read_text(encoding="utf-8")
    fillable_match = re.search(r"\$fillable\s*=\s*\[", source)
    if not fillable_match:
        return None
    fillable = php_block(source, source.find("[", fillable_match.start()))
    if not fillable:
        return None
    fields = re.findall(r"""['"]([^'"]+)['"]""", fillable)
    hidden_match = re.search(r"\$hidden\s*=\s*\[", source)
    if hidden_match:
        hidden_block = php_block(source, source.find("[", hidden_match.start()))
        hidden = set(re.findall(r"""['"]([^'"]+)['"]""", hidden_block or ""))
    else:
        hidden = set()

    cast_rules = {}
    methods = php_methods(source)
    casts_method = methods.get("casts")
    if casts_method:
        cast_source = casts_method["body"]
        for field, cast in re.findall(
            r"""['"]([^'"]+)['"]\s*=>\s*['"]([^'"]+)['"]""", cast_source
        ):
            cast_rules[field] = cast

    properties = {"id": {"type": "integer", "description": "Unique resource identifier."}}
    validation = validation_rules(action_info)
    for field in fields:
        if field in hidden:
            continue
        rules = validation.get(field)
        if rules:
            properties[field] = rule_schema(field, rules)
            properties[field].pop("description", None)
            continue
        cast = cast_rules.get(field, "")
        if cast in ("integer", "int"):
            field_schema = {"type": "integer"}
        elif cast in ("boolean", "bool"):
            field_schema = {"type": "boolean"}
        elif cast in ("float", "double", "decimal"):
            field_schema = {"type": "number"}
        elif cast in ("array", "json", "object"):
            field_schema = {"type": "object", "additionalProperties": True}
        elif cast in ("date", "datetime", "immutable_datetime"):
            field_schema = {"type": "string", "format": "date-time"}
        elif field.endswith("_id"):
            field_schema = {"type": "integer"}
        else:
            field_schema = {"type": "string"}
        field_schema["description"] = describe_field(field)
        properties[field] = field_schema
    properties["created_at"] = {"type": "string", "format": "date-time"}
    properties["updated_at"] = {"type": "string", "format": "date-time"}
    return {
        "type": "object",
        "description": f"{model_name} resource representation.",
        "properties": properties,
        "additionalProperties": True,
    }


def response_schema(action_info):
    generic = {"type": "object", "additionalProperties": True}
    if not action_info:
        return generic, 200
    body = action_info["body"]

    success_calls = php_call_arguments(
        body, r"\$this->(?:okPaginated|ok|created)\s*\("
    )
    if success_calls:
        call_match = re.search(r"\$this->(okPaginated|ok|created)\s*\(", body)
        call_type = call_match.group(1)
        arguments = success_calls[0]
        if call_type == "okPaginated":
            item_schema = model_response_schema(action_info, "$item")
            data_schema = {
                "type": "array",
                "items": item_schema or {
                    "type": "object",
                    "additionalProperties": True,
                },
            }
            meta_schema = {"$ref": "#/components/schemas/PaginationMeta"}
        else:
            data_schema = {"nullable": True}
            if arguments and arguments[0] and arguments[0] != "null":
                data_schema = model_response_schema(action_info, arguments[0])
                if data_schema is None:
                    data_schema = php_expression_schema(arguments[0], "data")
            meta_schema = {"type": "object", "additionalProperties": True}
        return (
            {
                "type": "object",
                "required": ["success", "data", "meta", "errors"],
                "properties": {
                    "success": {"type": "boolean", "enum": [True]},
                    "data": data_schema,
                    "meta": meta_schema,
                    "errors": {"type": "array", "items": {}},
                },
            },
            201 if call_type == "created" else 200,
        )

    direct_responses = []
    for arguments in php_call_arguments(body, r"response\(\)->json\s*\("):
        if arguments and arguments[0].startswith("["):
            schema = php_expression_schema(arguments[0])
            status = (
                int(arguments[1])
                if len(arguments) > 1 and arguments[1].isdigit()
                else 200
            )
            direct_responses.append((arguments[0], schema, status))
    for expression, schema, status in direct_responses:
        if re.search(r"""['"]success['"]\s*=>\s*true""", expression):
            return infer_response_variables(schema, expression, body), status
    if direct_responses:
        expression, schema, status = direct_responses[-1]
        return infer_response_variables(schema, expression, body), status
    return generic, 200


def error_statuses(action_info):
    if not action_info:
        return set()
    body = action_info["body"]
    statuses = set()
    for arguments in php_call_arguments(body, r"\$this->(?:fail|error)\s*\("):
        if len(arguments) > 1 and arguments[1].isdigit():
            statuses.add(int(arguments[1]))
    for arguments in php_call_arguments(body, r"response\(\)->json\s*\("):
        if len(arguments) > 1 and arguments[1].isdigit():
            statuses.add(int(arguments[1]))
    if "ValidationException::withMessages" in body:
        statuses.add(422)
    if re.search(r"\b(?:abort|abort_if|abort_unless)\s*\(\s*404\b", body):
        statuses.add(404)
    return statuses


def operation_description(action_info, matches, middleware):
    parts = []
    if action_info:
        doc = clean_docblock(action_info.get("doc", ""))
        if doc:
            parts.append(doc)
        if action_info.get("inherited_from"):
            parts.append(
                "Uses shared CRUD behavior from "
                + action_info["inherited_from"]
                + "; request fields are derived from the resource controller's validation rules."
            )
            child_source = action_info.get("child_source", "")
            body = action_info.get("body", "")
            mismatches = []
            if "$this->rulesStore" in body and "$createRules" in child_source:
                mismatches.append(
                    "the resource declares `$createRules`, but the base controller reads `$rulesStore`"
                )
            if "$this->rulesUpdate" in body and "$updateRules" in child_source:
                mismatches.append(
                    "the resource declares `$updateRules`, but the base controller reads `$rulesUpdate`"
                )
            if mismatches:
                parts.append(
                    "Implementation note: "
                    + "; ".join(mismatches)
                    + ". The endpoint's runtime validation may therefore differ from the documented example."
                )
    for example in matches:
        if example.get("name"):
            postman_title = "Postman request: " + example["name"] + "."
            if postman_title not in parts:
                parts.append(postman_title)
        description = example.get("description")
        if description and description not in parts:
            parts.append(description)
    if not parts:
        parts.append("Laravel action: `" + (action_info or {}).get("action", "registered route") + "`.")
    if middleware:
        permissions = [
            item.split(":", 1)[1]
            for item in middleware
            if "PermissionMiddleware:" in item or "RoleMiddleware:" in item
        ]
        if permissions:
            parts.append("Required permission/role: " + ", ".join(permissions) + ".")
    postman_folders = sorted(
        {example["folder"] for example in matches if example.get("folder")}
    )
    if postman_folders:
        parts.append("Postman collection: " + "; ".join(postman_folders) + ".")
    return "\n\n".join(parts)


def operation_tag(path):
    parts = path.strip("/").split("/")
    if parts[:3] == ["api", "v1", "mobile"]:
        if len(parts) > 3 and parts[3] in ("resident", "collector"):
            label = "Mobile " + parts[3].title()
            if len(parts) > 4:
                resource = parts[4].replace("-", " ").title()
                return f"{label} - {resource}"
            return label
        return "Mobile General"
    if len(parts) > 2 and parts[:2] == ["api", "v1"]:
        if parts[2] == "auth":
            return "Authentication"
        return parts[2].replace("-", " ").title()
    if len(parts) > 1 and parts[0] == "api":
        return "Webhooks & Integrations"
    return "Other API"


def humanize_action(action):
    controller, _, method = action.rpartition("@")
    controller = controller.rsplit("\\", 1)[-1].removesuffix("Controller")
    words = re.sub(r"([a-z0-9])([A-Z])", r"\1 \2", method)
    return f"{controller}: {words.lower()}"


def make_parameter(name, location, required, example=None):
    parameter_type = (
        "integer"
        if (
            location == "path"
            and (name.lower() == "id" or name.lower().endswith("_id"))
        )
        or name in ("page", "page.size", "per_page", "limit", "offset")
        else "object"
        if name == "filter"
        else "string"
    )
    parameter_format = (
        "date"
        if any(part in name.lower() for part in ("date", "_from", "_to"))
        else None
    )
    parameter = {
        "name": name,
        "in": location,
        "required": required,
        "description": describe_field(name),
        "schema": {"type": parameter_type},
    }
    if parameter_format and parameter_type == "string":
        parameter["schema"]["format"] = parameter_format
    if example is not None:
        parameter["example"] = example
        if str(example).isdigit() and parameter["schema"]["type"] == "string":
            parameter["schema"] = {"type": "integer"}
    return parameter


def build_openapi(routes, examples):
    paths = {}
    tags = set()
    components = {
        "schemas": {
            "PaginationMeta": {
                "type": "object",
                "description": "Pagination information for list responses.",
                "required": ["current_page", "per_page", "total", "last_page"],
                "properties": {
                    "current_page": {"type": "integer", "minimum": 1},
                    "per_page": {"type": "integer", "minimum": 1},
                    "total": {"type": "integer", "minimum": 0},
                    "last_page": {"type": "integer", "minimum": 1},
                },
            },
            "ApiErrorResponse": {
                "type": "object",
                "description": "Standard Laravel API error envelope.",
                "required": ["success", "data", "meta", "errors"],
                "properties": {
                    "success": {"type": "boolean", "example": False},
                    "data": {},
                    "meta": {"type": "object", "additionalProperties": True},
                    "errors": {
                        "type": "object",
                        "properties": {
                            "message": {"type": "string"},
                            "details": {
                                "type": "object",
                                "additionalProperties": {
                                    "type": "array",
                                    "items": {"type": "string"},
                                },
                            },
                        },
                    },
                },
            },
        }
    }
    controller_cache = {}

    for route in routes:
        path = normalize_path(route["uri"])
        tag = operation_tag(path)
        tags.add(tag)
        middleware = route.get("middleware", [])

        for method in route["method"].split("|"):
            method = method.lower()
            if method in ("head", "options"):
                continue

            matches = examples.get((method, path), [])
            action = route.get("action", "Unknown")
            action_info = controller_action(action, controller_cache)
            if action_info:
                action_info["action"] = action
            doc_text = clean_docblock(action_info.get("doc", "")) if action_info else ""
            summary = next(
                (example.get("name") for example in matches if example.get("name")),
                None,
            ) or (doc_text.split("\n", 1)[0] if doc_text else humanize_action(action))
            operation_id = re.sub(r"[^A-Za-z0-9_]", "_", action + "_" + method)
            rules = validation_rules(action_info)
            response, success_status = response_schema(action_info)
            request_definition = request_body(
                matches,
                rules,
                re.sub(r"[^A-Za-z0-9_]", "_", operation_id + "_Request"),
            )
            operation = {
                "tags": [tag],
                "summary": summary,
                "operationId": operation_id,
                "description": operation_description(action_info, matches, middleware),
                "parameters": [],
                "responses": {
                    str(success_status): {
                        "description": (
                            "Resource created successfully."
                            if success_status == 201
                            else "Successful response."
                        ),
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": (
                                        "#/components/schemas/"
                                        + re.sub(
                                            r"[^A-Za-z0-9_]",
                                            "_",
                                            operation_id + "_Response",
                                        )
                                    )
                                }
                            }
                        },
                    },
                    "500": {
                        "description": "Unexpected server error.",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/ApiErrorResponse"
                                }
                            }
                        },
                    },
                },
                "x-laravel-middleware": middleware,
            }

            for name in re.findall(r"\{([^}]+)\}", path):
                operation["parameters"].append(make_parameter(name, "path", True))

            seen_query = set()
            for example in matches:
                for query in example["request"].get("url", {}).get("query", []):
                    name = query.get("key")
                    if name and name not in seen_query and not query.get("disabled"):
                        operation["parameters"].append(
                            make_parameter(
                                name,
                                "query",
                                False,
                                query.get("value") if query.get("value") else None,
                            )
                        )
                        seen_query.add(name)
            if method == "get" and action_info:
                for name in re.findall(
                    r"(?:->|::)?(?:query|input)\s*\(\s*['\"]([^'\"]+)['\"]",
                    action_info["body"],
                ):
                    if name not in seen_query:
                        operation["parameters"].append(
                            make_parameter(name, "query", False)
                        )
                        seen_query.add(name)

            is_authenticated = any(
                item.startswith("auth:")
                or "Middleware\\Authenticate:" in item
                for item in middleware
            )
            has_permission_middleware = any(
                "PermissionMiddleware:" in item or "RoleMiddleware:" in item
                for item in middleware
            )

            if is_authenticated:
                operation["security"] = [{"bearerAuth": []}]
                operation["responses"]["401"] = {
                    "description": "Unauthenticated.",
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/ApiErrorResponse"
                            }
                        }
                    },
                }
            if has_permission_middleware:
                operation["responses"]["403"] = {
                    "description": "Forbidden.",
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/ApiErrorResponse"
                            }
                        }
                    },
                }

            if rules:
                operation["responses"]["422"] = {
                    "description": "Request validation failed.",
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/ApiErrorResponse"
                            }
                        }
                    },
                }
            if action_info and re.search(r"findOrFail|firstOrFail", action_info["body"]):
                operation["responses"]["404"] = {
                    "description": "The requested resource was not found.",
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/ApiErrorResponse"
                            }
                        }
                    },
                }
            error_descriptions = {
                400: "The request was rejected or contains invalid input.",
                401: "Unauthenticated.",
                403: "Forbidden.",
                404: "The requested resource was not found.",
                409: "The request conflicts with the current resource state.",
                422: "Request validation failed.",
                429: "Too many requests.",
            }
            for status in error_statuses(action_info):
                if status == success_status or str(status) in operation["responses"]:
                    continue
                operation["responses"][str(status)] = {
                    "description": error_descriptions.get(
                        status, "The operation failed."
                    ),
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/ApiErrorResponse"
                            }
                        }
                    },
                }

            if request_definition:
                request_body_spec, request_schema = request_definition
                operation["requestBody"] = request_body_spec
                components["schemas"][re.sub(
                    r"[^A-Za-z0-9_]", "_", operation_id + "_Request"
                )] = request_schema

            response_name = re.sub(
                r"[^A-Za-z0-9_]", "_", operation_id + "_Response"
            )
            response.setdefault("description", f"Successful response for {summary}.")
            add_schema_descriptions(response)
            components["schemas"][response_name] = response

            paths.setdefault(path, {})[method] = operation

    return {
        "openapi": "3.0.3",
        "info": {
            "title": "Smart Environment API",
            "version": "1.0.0",
            "description": (
                "Generated from Laravel's registered API routes, controller "
                "validation rules, and matching Postman request examples. "
                "Authenticated endpoints accept a Laravel bearer token."
            ),
        },
        "servers": [
            {"url": "http://127.0.0.1:8000", "description": "Local development"},
            {"url": "http://localhost:8000", "description": "Local development"},
        ],
        "tags": [{"name": tag} for tag in sorted(tags)],
        "paths": paths,
        "components": {
            **components,
            "securitySchemes": {
                "bearerAuth": {
                    "type": "http",
                    "scheme": "bearer",
                    "bearerFormat": "Token",
                }
            }
        },
    }


def main():
    result = subprocess.run(
        ["php", "artisan", "route:list", "--path=api", "--json"],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
    )
    routes = json.loads(result.stdout)
    document = build_openapi(routes, collection_operations())
    OUTPUT.write_text(
        json.dumps(document, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )
    operation_count = sum(len(methods) for methods in document["paths"].values())
    print(f"Wrote {operation_count} operations to {OUTPUT}")


if __name__ == "__main__":
    main()
