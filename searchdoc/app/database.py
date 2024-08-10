from flask_sqlalchemy import SQLAlchemy
import queue
import time

db = SQLAlchemy()
message_queue = queue.Queue()

def connect_to_db_with_retry(app, retries=5, delay=2):
    from models import get_user_ids
    for i in range(retries):
        try:
            with app.app_context():
                ids_370, ids_371, ids_372, ids_814 = get_user_ids()
                if app.config["DEBUG"]:
                    print(f"basic: {ids_370}, pro: {ids_371}, ultimate: {ids_372}, free: {ids_814}")
                return ids_370, ids_371, ids_372, ids_814
        except Exception as e:
            print(f"Attempt {i+1} failed: {e}")
            time.sleep(delay)
    else:
        print("Failed to connect to the database after several attempts.")
        return None, None, None, None  # Return a tuple of Nones if all retries fail
