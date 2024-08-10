from google.cloud import storage
from passlib.hash import phpass
import pandas as pd 
from google.cloud import storage
import os,subprocess
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

def load_data(file_path):
    return pd.read_csv(file_path)

def vm_cost(vm_type,disk_type,hours,disk_size):
    vmPrices = {
            "n1-standard-2": 0.6,
            "n1-standard-4": 1.25,
            "n1-standard-8": 2.5,
            "n1-standard-16": 5.0,
            "n1-standard-32": 10.0,
            "c2d-standard-2": 0.6,
            "c2d-standard-4": 1.25,
            "c2d-standard-8": 2.5,
            "c2d-standard-16": 5.0,
            "c2d-standard-32": 10.0,
        }
    diskPrices = {
            "pd-standard": 0.0003,
            "pd-balanced": 0.0006,
            "pd-ssd": 0.0012
        }
    
    vmPrice = vmPrices[vm_type]
    vmCost = vmPrice * hours
    print(diskPrices[disk_type] , disk_size , hours)
    diskCost = diskPrices[disk_type] * float(disk_size) * hours
    totalCost = vmCost + diskCost
    return totalCost