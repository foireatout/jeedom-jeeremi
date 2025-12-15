#!/python_venv python3
import requests
import json
import sys

API_BASE_URL = "https://remi2.urbanhello.com/parse"
PARSE_APP_ID = "jf1a0bADt5fq"


def login(username, password):
    url = f"{API_BASE_URL}/login"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json"
    }
    data = {"username": username, "password": password}
    response = requests.post(url, headers=headers, json=data, timeout=10)
    response.raise_for_status()
    return response.json()


def get_user_info(session_token, user_object_id, attribute=None):
    url = f"{API_BASE_URL}/users/{user_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.get(url, headers=headers, timeout=10)
    response.raise_for_status()
    data = response.json()
    return data.get(attribute) if attribute else data


def get_remi_info(session_token, remi_object_id, attribute=None):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.get(url, headers=headers, timeout=10)
    response.raise_for_status()
    data = response.json()
    return data.get(attribute) if attribute else data


def set_remi_luminosity(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.put(url, headers=headers, json={"luminosity": level}, timeout=10)
    response.raise_for_status()
    return response.json()


def set_remi_volume(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.put(url, headers=headers, json={"volume": level}, timeout=10)
    response.raise_for_status()
    return response.json()


FACE_MAP = {
    "sleepyFace": "rnAltoFwYC",
    "awakeFace": "fIjF0yWRxX",
    "blankFace": "GDaZOVdRqj",
    "semiAwakeFace": "9faiiPGBVv"
}
FACE_MAP_INV = {v: k for k, v in FACE_MAP.items()}


def get_current_face(session_token, remi_object_id):
    remi = get_remi_info(session_token, remi_object_id)
    face = remi.get("face", {})
    fid = face.get("objectId")
    return FACE_MAP_INV.get(fid, "UnknownFace")


def set_face_by_name(session_token, remi_object_id, face_name):
    if face_name not in FACE_MAP:
        raise Exception(f"Unknown face '{face_name}'")

    pointer = {
        "__type": "Pointer",
        "className": "Face",
        "objectId": FACE_MAP[face_name]
    }

    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.put(url, headers=headers, json={"face": pointer}, timeout=10)
    response.raise_for_status()
    return response.json()


if __name__ == "__main__":
    try:
        cmd = sys.argv[1]

        if cmd == "login":
            print(json.dumps(login(sys.argv[2], sys.argv[3])))

        elif cmd == "user_info":
            print(json.dumps(get_user_info(sys.argv[2], sys.argv[3])))

        elif cmd == "remi_info":
            print(json.dumps(get_remi_info(sys.argv[2], sys.argv[3])))

        elif cmd == "set_luminosity":
            print(json.dumps(set_remi_luminosity(sys.argv[2], sys.argv[3], int(sys.argv[4]))))

        elif cmd == "set_volume":
            print(json.dumps(set_remi_volume(sys.argv[2], sys.argv[3], int(sys.argv[4]))))

        elif cmd == "get_face":
            print(get_current_face(sys.argv[2], sys.argv[3]))

        elif cmd == "set_face":
            print(json.dumps(set_face_by_name(sys.argv[2], sys.argv[3], sys.argv[4])))

        else:
            print("Commande inconnue")

    except Exception as e:
        print(json.dumps({
            "error": "API_ERROR",
            "message": str(e)
        }))
        sys.exit(1)