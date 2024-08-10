from google.cloud import storage
from passlib.hash import phpass
from google.cloud import storage
import os,subprocess
import google.generativeai as genai
import pickle

genai.configure(api_key=os.getenv("API_KEY"))
# Create the model
# See https://ai.google.dev/api/python/google/generativeai/GenerativeModel
def create_chat_session():
    generation_config = {
    "temperature": 0.9,
    "top_p": 1,
    "top_k": 0,
    "max_output_tokens": 2048,
    "response_mime_type": "text/plain",
    }
    model = genai.GenerativeModel(
    model_name="gemini-1.0-pro",
    generation_config=generation_config,
    # safety_settings = Adjust safety settings
    # See https://ai.google.dev/gemini-api/docs/safety-settings
    )
    chat_session = model.start_chat(
    history=[
    ]
    )
    return chat_session

def chatbot_model(chat_session,prompt):
    response = chat_session.send_message(prompt)
    return response.text

FLASK_DEBUG = os.getenv("FLASK_DEBUG")
def save_gcs(bucket_name,data):
    client = storage.Client()
    bucket = client.bucket(bucket_name)
    blob = bucket.blob(os.path.join('data', data.filename))
    full_path = f"gs://{bucket_name}/data/{data.filename}"
    command = f"gsutil ls {full_path} 2>/dev/null"
    result = subprocess.run(command, shell=True, capture_output=True, text=True)
    if result.stdout.strip()=="":
        blob.upload_from_file(data)
        if FLASK_DEBUG:
            print("Successfuly saved to gcs.")
    else:
        print("Data exist.")

def read_log_file_from_gcs(bucket_name, log_file_name):
    # Initialize a storage client
    client = storage.Client()

    # Create the bucket object
    bucket = client.get_bucket(bucket_name)

    # List blobs in the bucket and find the matching log file
    blobs = bucket.list_blobs()
    matching_blob = None

    for blob in blobs:
        if log_file_name in blob.name:
            matching_blob = blob
            break

    if matching_blob:
        # Download and read the content of the log file
        log_content = matching_blob.download_as_text()

        return log_content
    else:
        print("No matching log file found.")
        return None



def verify_wordpress_password(hashed_password, plain_password):
    """
    Verify a WordPress hashed password with a plain password.

    Args:
        hashed_password (str): The hashed password from the WordPress database.
        plain_password (str): The plain password to verify.

    Returns:
        bool: True if the password matches, False otherwise.
    """
    # Use passlib to check the password
    return phpass.verify(plain_password, hashed_password)

