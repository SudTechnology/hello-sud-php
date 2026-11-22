#!/usr/bin/env python3
"""Exercise a running demo without printing credentials or tokens.

For signed mode, set VERIFY_CALLBACK_SIGNATURE=true and use the same APP_ID and
APP_SECRET as the server. Serialize once, sign those bytes, and send them unchanged.
"""
import hashlib
import hmac
import json
import os
import sys
import time
import urllib.error
import urllib.request
import uuid

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8000").rstrip("/")
SIGNED = os.environ.get("VERIFY_CALLBACK_SIGNATURE", "false").lower() == "true"
APP_ID = os.environ.get("APP_ID", "")
SECRET = os.environ.get("APP_SECRET", "")
if SIGNED and (not APP_ID or not SECRET):
    raise SystemExit("Set APP_ID and APP_SECRET to match the server before signed tests.")


def encode(data):
    return json.dumps(data, ensure_ascii=False, separators=(",", ":")).encode("utf-8")


def sign(body, app_id=APP_ID, secret=SECRET):
    timestamp = str(int(time.time() * 1000))
    nonce = uuid.uuid4().hex
    content = f"{app_id}\n{timestamp}\n{nonce}\n".encode() + body + b"\n"
    return {
        "Sud-AppId": app_id,
        "Sud-Timestamp": timestamp,
        "Sud-Nonce": nonce,
        "Sud-Signature": hmac.new(secret.encode(), content, hashlib.sha1).hexdigest(),
    }


def request(path, body=None, headers=None, method=None, content_type="application/json"):
    if headers is None:
        headers = sign(body or b"") if SIGNED and path != "/api/getCode" else {}
    req = urllib.request.Request(
        BASE + path, data=body, method=method,
        headers={"Content-Type": content_type, **headers},
    )
    try:
        response = urllib.request.urlopen(req, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.status, json.load(response)


def check(condition, name):
    if not condition:
        raise AssertionError("FAIL: " + name)
    print("PASS: " + name)


def success(path, body=None, headers=None):
    status, result = request(path, body, headers)
    check(status == 200 and result.get("ret_code") == 0, path)
    return result["data"]


def rejected(path, body, expected, headers=None, method=None, content_type="application/json"):
    status, result = request(path, body, headers, method, content_type)
    check(status == expected and result.get("ret_code") == 1
          and isinstance(result.get("data"), dict), f"Reject {path}: HTTP {expected}")
    return result


success("/health", headers={})
code = success("/api/getCode?uid=test-user-001", headers={})["code"]
# getCode must ignore callback headers even when verification is enabled.
success("/api/getCode?uid=test-user-001", headers={"Sud-Signature": "invalid"})
body = encode({"code": code, "future_field": {"supported_later": True}})
token = success("/api/getSSToken", body)
check(token["user_info"]["uid"] == "test-user-001"
      and all(k in token["user_info"] for k in ("nick_name", "avatar_url", "gender"))
      and isinstance(token["expire_date"], int), "Mock profile and millisecond expiry")
time.sleep(1.1)
refreshed = success("/api/updateSSToken", encode({"ss_token": token["ss_token"]}))
check(refreshed["expire_date"] > token["expire_date"], "Extended expiry")
for path, field in [("/api/getSSToken", "code"), ("/api/updateSSToken", "ss_token")]:
    error = rejected(path, encode({field: "invalid-token"}), 400)
    check(error["sdk_error_code"] == 1002, "SDK verification remains active")
    for value in ({}, {field: None}, {field: " "}, {field: 123}, {field: []}):
        rejected(path, encode(value), 400)
for body_bad in (b"{", b"[]", b"{} {}"):
    rejected("/api/getSSToken", body_bad, 400)
rejected("/api/getSSToken", b"{}", 400, content_type="text/plain")
rejected("/api/getCode", None, 400, headers={})
rejected("/", None, 404, headers={})
rejected("/api/getSSToken", None, 405, method="GET")

# A non-ASCII unknown field, CRLF, and whitespace must survive raw-body verification.
raw = ('{\r\n "future_field":"用户", "code":' + json.dumps(code) + '\r\n}').encode()
success("/api/getSSToken", raw)
if SIGNED:
    for path in ("/api/getSSToken", "/api/updateSSToken", "/api/future"):
        rejected(path, b"{", 403, headers={})
    headers = sign(raw)
    rejected("/api/getSSToken", raw + b" ", 403, headers=headers)
    rejected("/api/getSSToken", raw.replace(b"\r\n", b"\n"), 403, headers=headers)
    rejected("/api/getSSToken", raw, 403, headers=sign(raw, app_id="wrong-app"))
    rejected("/api/getSSToken", raw, 403, headers=sign(raw, secret="wrong-secret"))
    headers = sign(raw)
    headers["Sud-Signature"] = "0" * 40
    rejected("/api/getSSToken", raw, 403, headers=headers)
    success("/api/getSSToken", raw + b" ")
else:
    success("/api/getSSToken", raw, headers={"Sud-Signature": "invalid"})
print("All HTTP checks passed (signature verification " + ("enabled" if SIGNED else "disabled") + ").")
print("Secrets and tokens were not printed.")
