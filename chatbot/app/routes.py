from flask import jsonify, request
from app import app
from auth import jwt_required, create_jwt_token, verify_jwt_token
from models import *
from database import db, connect_to_db_with_retry
import os
from utils import verify_wordpress_password, chatbot_model, create_chat_session
from flask_cors import cross_origin
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from flask_swagger_ui import get_swaggerui_blueprint

# Connect to database
global ids_370, ids_371, ids_372, ids_814
ids_370, ids_371, ids_372, ids_814 = connect_to_db_with_retry(app, retries=5, delay=2)

PROJECT_ID = os.getenv('project_id')

limiter = Limiter(
    get_remote_address,
    app=app,
    default_limits=["50 per day"],
    # storage_uri="memory://",
)

# Swagger UI configuration
SWAGGER_URL = '/docs'
API_URL = '/swagger.json'

swaggerui_blueprint = get_swaggerui_blueprint(
    SWAGGER_URL,
    API_URL,
    config={
        'app_name': "Chatbot Backend API"
    }
)

app.register_blueprint(swaggerui_blueprint, url_prefix=SWAGGER_URL)

@app.route("/", methods=["GET", 'OPTIONS'])
@cross_origin()
def make_salute():
    if request.method == 'OPTIONS':
        # Respond to preflight request
        headers = {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'POST',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization',
        }
        return ('', 204, headers)
    domain = request.host_url.rstrip('/')  # Get the base URL of the request
    host = request.host 
    print(host,domain)
    return jsonify({"message": f"Hello, welcome to the Chatbot Backend API! You can visit {domain}/docs for API docs."})

@app.route("/swagger.json")
@cross_origin()
def swagger_json():
    domain = request.host # Get the host of the current request
    return jsonify({
        "swagger": "2.0",
        "info": {
            "title": "Chatbot Backend API",
            "description": "API documentation for the Chatbot Backend.",
            "version": "1.0.0"
        },
        "host": domain,
        "basePath": "/",
        "schemes": ["https",
            "http"
        ],
        "paths": {
            "/": {
                "get": {
                    "summary": "Make Salute",
                    "description": "Returns a greeting message",
                    "responses": {
                        "200": {
                            "description": "A successful response",
                            "schema": {
                                "type": "object",
                                "properties": {
                                    "message": {
                                        "type": "string"
                                    }
                                }
                            }
                        }
                    }
                }
            },
            "/generate_content": {
                "post": {
                    "summary": "Generate Content",
                    "description": "Generates content based on user prompt",
                    "parameters": [
                        {
                            "name": "body",
                            "in": "body",
                            "required": True,
                            "schema": {
                                "type": "object",
                                "properties": {
                                "user_email": {
                                    "type": "string",
                                    "description": "The email address of the user."
                                },
                                "application_password": {
                                    "type": "string",
                                    "description": "The application-specific hash password for the api user. You can create and list from https://genaideeplabs.com/account2/#ApplicationPassword/ . You can find in APP Passwords table's Application Password HASH (API) field. Example hash app password: $P$BaKgXM2lxXnGy/RaPByi.5ksYBvX4Q/"
                                },
                                "prompt": {
                                    "type": "string",
                                    "description": "The prompt provided by the user to generate content."
                                }
                            }
                            }
                        }
                    ],
                    "responses": {
                        "200": {
                            "description": "A successful response",
                            "schema": {
                                "type": "object",
                                "properties": {
                                    "response": {
                                        "type": "string"
                                    }
                                }
                            }
                        },
                        "400": {
                            "description": "Invalid input"
                        },
                        "401": {
                            "description": "Unauthorized"
                        }
                    }
                }
            }
        }
    })

@app.route('/generate_content', methods=['POST', 'OPTIONS'])
# @jwt_required
@cross_origin()
@limiter.limit("50 per day")
def generate_content_fn():
    if request.method == 'OPTIONS':
        # Respond to preflight request
        headers = {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'POST',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization',
        }
        return ('', 204, headers)
    
    chat_session = create_chat_session()

    data = request.get_json()
    if not data or 'prompt' not in data:
        return jsonify({"error": "Invalid input"}), 400
    user_email = data["user_email"]

    try: 
        application_password = data["application_password"]
        db_app = get_application_password(user_email)
        print("db_app_pass:",db_app,"request_app_pass:",application_password)
        assert db_app == application_password
    except:
        return jsonify({"response": "Unauthorized login or no application password. You can crate application password from https://genaideeplabs.com/account2/#ApplicationPassword/ ."})

    if user_email not in (ids_370 + ids_371 + ids_372):
        print("user_email",user_email)
        return jsonify({"response": "Unauthorized login, you register from https://genaideeplabs.com/register2."})

    response = chatbot_model(chat_session=chat_session, prompt=data["prompt"])
    return jsonify({"response": response})

if __name__ == "__main__":
    connect_to_db_with_retry(app)
