from datetime import datetime, timedelta
from jose import jwt, JWTError
from flask import request, redirect, session
from functools import wraps
import os


JWT_SECRET_KEY = os.getenv("JWT_SECRET_KEY")
JWT_ALGORITHM = os.getenv("JWT_ALGORITHM")
JWT_EXPIRATION_DELTA = timedelta(days=1)
register_url = os.getenv("register_url")
FLASK_DEBUG = os.getenv("FLASK_DEBUG")

def create_jwt_token(user_email, user_app_pass):
    expiration = datetime.utcnow() + JWT_EXPIRATION_DELTA
    payload = {
        'user_email': user_email,
        "user_app_pass": user_app_pass,
        'exp': expiration
    }
    token = jwt.encode(payload, JWT_SECRET_KEY, algorithm=JWT_ALGORITHM)
    return token

def verify_jwt_token(token):
    try:
        payload = jwt.decode(token, JWT_SECRET_KEY, algorithms=[JWT_ALGORITHM])
        return {"user_email": payload['user_email'], "user_app_pass": payload['user_app_pass']}
    except JWTError:
        return None

def jwt_required(fn):
    @wraps(fn)
    def wrapper(*args, **kwargs):
        if not FLASK_DEBUG:
            token = request.cookies.get('jwt_token')
            if not token:
                if FLASK_DEBUG:
                    print("jwt token not found")
                return redirect(register_url)
            try:
                payload = jwt.decode(token, JWT_SECRET_KEY, algorithms=[JWT_ALGORITHM])
                if FLASK_DEBUG:
                    print("payload", payload)
                session['user_email'] = payload['user_email']
                session['user_app_pass'] = payload['user_app_pass']
            except JWTError:
                return redirect(register_url)
        return fn(*args, **kwargs)
    return wrapper

def get_jwt_token_from_session():
    token = session.get('jwt_token')
    if not token:
        return None
    try:
        decoded_token = jwt.decode(token, JWT_SECRET_KEY, algorithms=[JWT_ALGORITHM])
        return decoded_token
    except jwt.ExpiredSignatureError:
        return None
    except jwt.InvalidTokenError:
        return None
