import json
import os
import urllib.request
import urllib.error
from urllib.parse import unquote_plus

HEROKU_APP_URL = os.environ["HEROKU_APP_URL"]        # e.g. https://your-app.herokuapp.com
INTERNAL_RESTORE_TOKEN = os.environ["INTERNAL_RESTORE_TOKEN"]


def lambda_handler(event, context):
    for record in event.get("Records", []):
        s3_key = unquote_plus(record["s3"]["object"]["key"])

        if not s3_key.startswith("backups/"):
            print(f"Skipping non-backup key: {s3_key}")
            continue

        print(f"Triggering restore for: {s3_key}")

        payload = json.dumps({"s3_key": s3_key}).encode("utf-8")

        req = urllib.request.Request(
            url=f"{HEROKU_APP_URL}/internal/restore-from-s3",
            data=payload,
            headers={
                "Content-Type": "application/json",
                "X-Internal-Token": INTERNAL_RESTORE_TOKEN,
            },
            method="POST",
        )

        try:
            with urllib.request.urlopen(req, timeout=10) as response:
                body = response.read().decode("utf-8")
                print(f"Response {response.status}: {body}")
        except urllib.error.HTTPError as e:
            body = e.read().decode("utf-8")
            print(f"HTTP error {e.code}: {body}")
            raise
        except urllib.error.URLError as e:
            print(f"Request failed: {e.reason}")
            raise

    return {"statusCode": 200}
