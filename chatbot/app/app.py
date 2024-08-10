from flask import Flask
from dotenv import load_dotenv
import os
import pymysql
from utils import verify_wordpress_password
from flask_cors import CORS

load_dotenv()
app = Flask(__name__)

cors = CORS(app, resources={
    r"/*": {
        "origins": "*",
        "supports_credentials": True
    }
}) # Enable CORS for all routes

app.secret_key = os.getenv("APP_SECRET_KEY")

# Load configurations
app.config['SQLALCHEMY_DATABASE_URI'] = f'mysql://{os.getenv("user")}:{os.getenv("password")}@{os.getenv("host")}/{os.getenv("database")}'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = os.getenv("SQLALCHEMY_TRACK_MODIFICATIONS")
app.config["DEBUG"] = os.getenv("FLASK_DEBUG")

# Ensure PyMySQL is used instead of MySQLdb
pymysql.install_as_MySQLdb()

from database import db
db.init_app(app)

# Initialize the database
with app.app_context():
    from routes import *
    db.create_all()

if __name__ == '__main__':
    app.run(debug=app.config["DEBUG"], host='0.0.0.0', port=8001)
