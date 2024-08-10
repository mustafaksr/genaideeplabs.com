from flask import jsonify, request, redirect, url_for, session, render_template, Response, flash, make_response
from app import app
from auth import jwt_required, create_jwt_token, verify_jwt_token
from models import *
from database import db, connect_to_db_with_retry
import os, subprocess
from utils import verify_wordpress_password, save_gcs, load_data, read_log_file_from_gcs,vm_cost
import uuid
from google.cloud import pubsub_v1
import queue, threading
from sqlalchemy import text, desc

global ids_370, ids_371, ids_372, ids_814
ids_370, ids_371, ids_372, ids_814 = connect_to_db_with_retry(app, retries=5, delay=2)

register_url = os.getenv("register_url")
PROJECT_ID = os.getenv('project_id')
message_queue = queue.Queue()

# Global dictionary to hold message queues for each job UUID
job_message_queues = {}

@app.route('/', methods=['GET', 'POST'])
def index():

    session["completed_steps"] = {
        '_index': False,
        'define_job': False,
        'define_vm': False,
        'define_data': False,
        'define_task': False,
        'define_train': False,
        'summary': False,
        'train_logs': False,
        "start_training":False
    }
    if app.config["DEBUG"]:
        print(session["completed_steps"])

    if request.method == 'POST':
        
        user_data_form = request.form.to_dict()
        session['user_data_form'] = user_data_form
        if app.config["DEBUG"]:
            print(user_data_form)
        
        user_email = user_data_form.get('user_email')
        user_app_pass = user_data_form.get('user_app_pass').replace(" ","")

        user_id = (db.session.query(WpUser.id).filter(WpUser.user_email==user_email).first())[0]
        session["user_id"] = user_id
        if app.config["DEBUG"]:
            print("user_id:",user_id,"| user_email:",user_email,"| user_pass:",user_app_pass)

        try:
            emails = db.session.query( WpUser.user_email).all()
            emails = [x[0] for x in emails]
            #db_hash_pass = db.session.query( WpUser.user_pass).filter(WpUser.user_email==user_email).one()[0]
            db_hash_pass = get_application_password(user_email)
                
            if app.config["DEBUG"]:
                print(db_hash_pass)
                print("emails:", emails, "hash pass:",db_hash_pass, "user app pass:",user_app_pass)
            try:
                assert user_email in emails, flash("User not found in Database.")
            except:
                flash("User not found in Database.")
                return redirect(url_for('index'))
            if app.config["DEBUG"]:
                print(user_app_pass , db_hash_pass)

            assert verify_wordpress_password(db_hash_pass,user_app_pass)

        except:
            if app.config["DEBUG"]:
                flash("User email or User password not correct.")
                flash("You can register from https://genaideeplabs.com/register2/ and create application password.")
            return redirect(url_for('index'))
            

        session["user_email"] = user_email
        session["user_app_pass"] = user_app_pass

        if user_email in ids_370 + ids_371 + ids_372 + ids_814:
            token = create_jwt_token(user_email,user_app_pass)
            if app.config["DEBUG"]:
                print(token)
            response = make_response(redirect(url_for('define_job'),code=301))
            response.set_cookie('jwt_token', token, httponly=True, samesite='Lax')
            session["completed_steps"]["_index"] = True
            
           

            return response
        else:
            if app.config["DEBUG"]:
                print("Redirect to registration page with 401 Unauthorized status code")
            return redirect(register_url, code=401)
    user_data_form = session.get('user_data_form', {})
    return render_template('index.html', completed_steps=session["completed_steps"],user_data_form=user_data_form)

@app.route('/define_job' ,methods=['GET','POST'])
@jwt_required
def define_job():
    if session["completed_steps"]["_index"]:
        pass
    else:
        flash("Didn't login app. Please login app first.",category="warning")
        return redirect(register_url, code=302)
    if app.config["DEBUG"]:
        print(session["completed_steps"])

    jwt_token = request.cookies.get('jwt_token')

    if not jwt_token:
        if app.config["DEBUG"]:
            print("JWT token not present, redirect to register URL")
        return redirect(register_url, code=302)

    try:
        if app.config["DEBUG"]:
            print("Check JWT token and extract user ID")
        
            print(session.get('user_email'),session.get('user_app_pass'))
        payload = verify_jwt_token(jwt_token)
        if app.config["DEBUG"]:
            print(payload['user_email'],payload['user_app_pass'] )
        assert (session.get('user_email'),session.get('user_app_pass')) == (payload['user_email'],payload['user_app_pass'])   # Function to decode JWT token and get user ID
    except Exception as e:
        if app.config["DEBUG"]:
            print("JWT token is invalid, redirect to register URL")
        return redirect(register_url, code=302)

    user_email = session.get('user_email')
    if user_email not in ids_370 + ids_371 + ids_372 + ids_814:
        if app.config["DEBUG"]:
            print("User ID not found in ids_372, redirect to register URL")
        return redirect(register_url, code=302)
    
    job_names_uuid = db.session.query( AmUsersJobs.job_name,AmUsersJobs.model_name,AmUsersJobs.uuid,AmUsersJobs.status,AmUsersJobs.machine_type,AmUsersJobs.disk_type,AmUsersJobs.disk_size,AmUsersJobs.start_time,AmUsersJobs.end_time).filter(AmUsersJobs.user_id==session["user_id"]).order_by(desc(AmUsersJobs.start_time)).all()
    #job_names_uuid = [x[0] for x in job_names_uuid]
    if app.config["DEBUG"]:
        print(job_names_uuid)

    if request.method == 'POST':
        
        session['job_form_data'] = request.form.to_dict()
        session["completed_steps"]["define_job"] =True
        data = request.form
        session['job_name'] = data['job_name']
        
        try:
            data['load_predictor']
            session['load_predictor'] = True
            if data['load_predictor_path'] =="":
                flash("You didn't select which model to load from table. First select load model from table then select load predictor.")
                return redirect(url_for("define_job"))
            session['load_predictor_path'] = data['load_predictor_path']
        except:
            session['load_predictor'] = False
            session['load_predictor_path'] = None

        job_names = db.session.query( AmUsersJobs.job_name).filter(AmUsersJobs.user_id==session["user_id"]).all()
        
        if app.config["DEBUG"]:
            print("load_predictor",session['load_predictor'])
            print("load_pload_predictor_uuid",f"|{session['load_predictor_path']}|",type(session['load_predictor_path']))
            print(job_names)
        

        # Generate a random UUID
        random_uuid = uuid.uuid4()
        # Convert UUID to string representation
        uuid_str = str(random_uuid)

        if app.config["DEBUG"]:
            print("uuid:",uuid_str)

        session['uuid_str'] = uuid_str

        job_names = db.session.query( AmUsersJobs.job_name).filter(AmUsersJobs.user_id==session["user_id"]).all()
        job_names = [x[0] for x in job_names]

        if app.config["DEBUG"]:
            print(job_names)

        if session['job_name'] in job_names:
            flash(f"Job Name: {session['job_name']} is not unique. Your previous job names are below Previous Jobs table.")
            return redirect(url_for('define_job'),code=301)

        return redirect(url_for('define_vm'),code=301)


    job_form_data = session.get('job_form_data', {})
    return render_template('define_job.html', completed_steps=session["completed_steps"], job_form_data=job_form_data,job_names_uuid=job_names_uuid)

@app.route('/define_vm' ,methods=['GET','POST'])
@jwt_required
def define_vm():
    if session["completed_steps"]["define_job"]:
        pass
    else:
        flash("Didn't login app. Please login app first.",category="warning")
        return redirect(register_url, code=302)

    if app.config["DEBUG"]:
        print(session["completed_steps"])

    jwt_token = request.cookies.get('jwt_token')

    if not jwt_token:
        if app.config["DEBUG"]:
            print("JWT token not present, redirect to register URL")
        return redirect(register_url, code=302)

    try:
        if app.config["DEBUG"]:
            print("Check JWT token and extract user ID")
        
            print(session.get('user_email'),session.get('user_app_pass'))
        payload = verify_jwt_token(jwt_token)
        if app.config["DEBUG"]:
            print(payload['user_email'],payload['user_app_pass'] )
        assert (session.get('user_email'),session.get('user_app_pass')) == (payload['user_email'],payload['user_app_pass'])   # Function to decode JWT token and get user ID
    except Exception as e:
        if app.config["DEBUG"]:
            print("JWT token is invalid, redirect to register URL")
        return redirect(register_url, code=302)

    user_email = session.get('user_email')
    if user_email not in ids_370 + ids_371 + ids_372 + ids_814:
        if app.config["DEBUG"]:
            print("User ID not found in ids_372, redirect to register URL")
        return redirect(register_url, code=302)
    flash(f"Unique UUID is: {session['uuid_str']}  for job: {session['job_name']}")
    if request.method == 'POST':
        
        session['vm_form_data'] = request.form.to_dict()
        session["completed_steps"]["define_vm"] =True
        data = request.form
        session['zone'] = data['zone']
        session['machine_name'] = data['machine_name']
        session['machine_type'] = data['machine_type']
        session['disk_size'] = data['disk_size']
        session['disk_name'] = f"pd-balanced-{data['disk_size']}GB"
        session['disk_type'] = data['disk_type']
        session['region'] = data['region']
        
        # Command to execute
        command = f"gcloud projects describe {PROJECT_ID} --format='value(projectNumber)'"

        # Use subprocess to run the command and capture the output
        result = subprocess.run(command, shell=True, capture_output=True, text=True)
        
        # Get the first default value from the output
        project_number = result.stdout.strip()
        session['project_number'] = project_number
        

        variables_tf_content = f"""
        variable "project_id" {{
        description = "Google cloud project id"
        default = "{PROJECT_ID}"
        sensitive = true
        }}

        variable "project_number" {{
        description = "Project Number"
        default = "{session['project_number']}"
        sensitive = true
        }}

        variable "zone" {{
        description = "Zone of Virtual Machine"
        default = "{data['zone']}"
        }}

        variable "machine_name" {{
        description = "Name of Virtual Machine"
        default = "{data['machine_name']}"
        }}

        variable "machine_type" {{
        description = "Type of virtual machine"
        default = "{data['machine_type']}"
        }}

        variable "disk_size" {{
        description = "Type of disk size in GB"
        default = {data['disk_size']}
        }}

        variable "disk_name" {{
        description = "Name of disk"
        default = "pd-balanced-{data['disk_size']}GB"
        }}

        variable "disk_type" {{
        description = "The GCE disk type. Such as pd-standard, pd-balanced or pd-ssd."
        default = "{data['disk_type']}"
        }}

        variable "image_type" {{
        description = "The image from which to initialize this disk."
        default = "projects/{PROJECT_ID}/global/images/ubuntu24-autogluon-automl-sysd9"
        }}

        variable "region" {{
        description = "Google Cloud Region"
        default = "{data['region']}"
        }}
        """

        with open('variables.tf', 'w') as f:
            f.write(variables_tf_content)
        return redirect(url_for('define_data'),code=301)


    vm_form_data = session.get('vm_form_data', {})
    return render_template('define_vm.html', completed_steps=session["completed_steps"], vm_form_data=vm_form_data)



@app.route('/define_data', methods=['GET', 'POST'])
@jwt_required
def define_data():
    if session["completed_steps"]["define_vm"]:
        pass
    else:
        flash("Please complete define vm step first. Use next button for next step.",category="warning")
        return redirect(url_for("define_vm"), code=302)
    
    jwt_token = request.cookies.get('jwt_token')

    if not jwt_token:
        if app.config["DEBUG"]:
            print("JWT token not present, redirect to register URL")
        return redirect(register_url, code=302)

    try:
        if app.config["DEBUG"]:
            print("Check JWT token and extract user ID")
        
            print(session.get('user_email'),session.get('user_app_pass'))
        payload = verify_jwt_token(jwt_token)
        if app.config["DEBUG"]:
            print(payload['user_email'],payload['user_app_pass'] )
        assert (session.get('user_email'),session.get('user_app_pass')) == (payload['user_email'],payload['user_app_pass'])   # Function to decode JWT token and get user ID
    except Exception as e:
        if app.config["DEBUG"]:
            print("JWT token is invalid, redirect to register URL")
        return redirect(register_url, code=302)

    user_email = session.get('user_email')
    if user_email not in ids_370 + ids_371 + ids_372 + ids_814:
        if app.config["DEBUG"]:
            print("User ID not found in ids_372, redirect to register URL")
        return redirect(register_url, code=302)
    
    if request.method == 'POST':
        session['data_form'] = request.form.to_dict()
        session["completed_steps"]['define_data'] = True
        train_data = request.files['train_data']
        test_data = request.files['test_data']
        model_name = request.form['model_name']

        model_names = db.session.query( AmUsersJobs.model_name).filter(AmUsersJobs.user_id==session["user_id"]).all()
        model_names = [x[0] for x in model_names]
        
        if app.config["DEBUG"]:
            print(model_names)

        if model_name in model_names:
            flash(f"Model Name: {model_name} is not unique. Your previous model names are {model_names}")
            return redirect(url_for('define_data'),code=301)

        # Upload files to Google Cloud Storage
        gs_train_path = f"gs://{session['uuid_str']}/data/{train_data.filename}"
        gs_test_path = f"gs://{session['uuid_str']}/data/{test_data.filename}"

        try:
            # create bucket for remote terraform state
            subprocess.check_call(["gsutil","mb",f"gs://{session['uuid_str']}"])
            # label to bucket app:automl-bucket
            subprocess.check_call(["gsutil","label", "ch" ,"-l","app:automl-bucket" ,f"gs://{session['uuid_str']}"])
            
            if app.config["DEBUG"]:
                print("gcs created." , "uuid:",session['uuid_str'])
        except:pass

        for data in [train_data,test_data]:
            save_gcs(session['uuid_str'],data)

        session['train_data_path'] = gs_train_path
        session['test_data_path'] = gs_test_path
        session['model_name'] = model_name

        
        return redirect(url_for('define_task'),code=301)
    data_form = session.get('data_form', {})
    return render_template('define_data.html',completed_steps=session["completed_steps"],data_form=data_form)

@app.route('/define_task', methods=['GET', 'POST'])
@jwt_required
def define_task():
    if session["completed_steps"]["define_data"]:
        pass
    else:
        flash("Please complete define data step first. Use next button for next step.",category="warning")
        return redirect(url_for("define_data"), code=302)

    jwt_token = request.cookies.get('jwt_token')

    if not jwt_token:
        if app.config["DEBUG"]:
            print("JWT token not present, redirect to register URL")
        return redirect(register_url, code=302)

    try:
        if app.config["DEBUG"]:
            print("Check JWT token and extract user ID")
        
            print(session.get('user_email'),session.get('user_app_pass'))
        payload = verify_jwt_token(jwt_token)
        if app.config["DEBUG"]:
            print(payload['user_email'],payload['user_app_pass'] )
        assert (session.get('user_email'),session.get('user_app_pass')) == (payload['user_email'],payload['user_app_pass'])   # Function to decode JWT token and get user ID
    except Exception as e:
        if app.config["DEBUG"]:
            print("JWT token is invalid, redirect to register URL")
        return redirect(register_url, code=302)

    user_email = session.get('user_email')
    if user_email not in ids_370 + ids_371 + ids_372 + ids_814:
        if app.config["DEBUG"]:
            print("User ID not found in ids_372, redirect to register URL")
        return redirect(register_url, code=302)
    

    if session["load_predictor"]:
            #TODO: load_predictor
            task_train_vars = db.session.query( AmUsersJobs.label ,AmUsersJobs.problem_type ,AmUsersJobs.evaluation_metric ,AmUsersJobs.prediction_type ,AmUsersJobs.split_type ,AmUsersJobs.preprocess ,AmUsersJobs.feature_engineering ).filter(AmUsersJobs.user_id==session["user_id"],AmUsersJobs.uuid==session["load_predictor_path"].split("//")[1].split("/")[0]).first()
            if app.config["DEBUG"]:
                print(task_train_vars)
            session["label"] = task_train_vars[0]
            session["problem_type"] = task_train_vars[1]
            session["evaluation_metric"] = task_train_vars[2]
            session["prediction_type"] = task_train_vars[2]
            flash(f"Task settings for uuid: {session['load_predictor_path'].split('//')[1].split('/')[0]}")
            flash(f"label: {session['label']}, problem_type: {session['problem_type']}, evaluation_metric: {session['evaluation_metric']}, prediction_type: {session['prediction']}")

    if request.method == 'POST':
        session['task_form'] = request.form.to_dict()
        session["completed_steps"]['define_task'] = True
        label = request.form['label']
        problem_type = request.form['problem_type']
        eval_metric = request.form['eval_metric']
        prediction = request.form['prediction']

        session['label'] = label
        session['problem_type'] = problem_type
        session['eval_metric'] = eval_metric
        session['prediction'] = prediction

        try:
                train_data_path = session.get('train_data_path')
                train_data = load_data(train_data_path)
                test_data_path = session.get('test_data_path')
                test_data = load_data(test_data_path)
                train_columns = train_data.columns.to_list()
                test_columns = test_data.columns.to_list()
                train_columns.remove(label)

                try:
                    if app.config["DEBUG"]:
                        print(f"train columns: {train_columns}")
                        print(f"test columns: {test_columns}")
                    assert train_columns == test_columns
                except:
                    flash("There are Train and Test columns mismatch.")
                    flash(f"train columns: {train_columns}")
                    flash(f"test columns: {test_columns}")
                    return redirect(url_for('define_data'),code=310)

        except:
            flash("You haven't defined data yet. Please define data first before define task.")
            return redirect(url_for('define_data'),code=311)

        return redirect(url_for('define_train'),code=301)
    try:
        train_data_path = session.get('train_data_path')
        train_data = load_data(train_data_path)
        columns = train_data.columns.tolist()
    except:
        flash("You haven't defined data yet. Please define data first before define task.")
        return redirect(url_for('define_data'),code=304)
    task_form = session.get('task_form', {})
    return render_template('define_task.html', columns=columns,completed_steps=session["completed_steps"], task_form=task_form)

@app.route('/define_train', methods=['GET', 'POST'])
@jwt_required
def define_train():

    if session["completed_steps"]["define_task"]:
        pass
    else:
        flash("Please complete define task step first. Use next button for next step.",category="warning")
        return redirect(url_for("define_task"), code=302)

    jwt_token = request.cookies.get('jwt_token')

    if not jwt_token:
        if app.config["DEBUG"]:
            print("JWT token not present, redirect to register URL")
        return redirect(register_url, code=302)

    try:
        if app.config["DEBUG"]:
            print("Check JWT token and extract user ID")
        
            print(session.get('user_email'),session.get('user_app_pass'))
        payload = verify_jwt_token(jwt_token)
        if app.config["DEBUG"]:
            print(payload['user_email'],payload['user_app_pass'] )
        assert (session.get('user_email'),session.get('user_app_pass')) == (payload['user_email'],payload['user_app_pass'])   # Function to decode JWT token and get user ID
    except Exception as e:
        if app.config["DEBUG"]:
            print("JWT token is invalid, redirect to register URL")
        return redirect(register_url, code=302)

    user_email = session.get('user_email')
    if user_email not in ids_370 + ids_371 + ids_372 + ids_814:
        if app.config["DEBUG"]:
            print("User ID not found in ids_372, redirect to register URL")
        return redirect(register_url, code=302)
    
    if session["load_predictor"]:
        #TODO: load_predictor
        task_train_vars = db.session.query( AmUsersJobs.split_type ,AmUsersJobs.preprocess ,AmUsersJobs.feature_engineering).filter(AmUsersJobs.user_id==session["user_id"],AmUsersJobs.uuid==session["load_predictor_path"].split("//")[1].split("/")[0]).first()
        print(task_train_vars)
        session["split_type"] = task_train_vars[0]
        session["preprocess"] = task_train_vars[1]
        session["feature_engineering"] = task_train_vars[2]
        flash(f"Train settings for uuid: {session['load_predictor_path'].split('//')[1].split('/')[0]}")
        flash(f"split_type: {session['split_type']}, preprocess: {session['preprocess']}, feature_engineering: {session['feature_engineering']}")

    if request.method == 'POST':
        session['train_form'] = request.form.to_dict()

        session["completed_steps"]['define_train'] = True

        split_type = request.form['split_type']
        preprocess = request.form.get('preprocess') == 'on'
        feature_engineering = request.form.get('feature_engineering') == 'on'
        hypertuning = request.form.get('hypertuning') == 'on'
        num_trials = int(request.form['num_trials'])
        preset = request.form['preset']
        time_limit = request.form['time_limit']

        session['split_type'] = split_type
        session['preprocess'] = preprocess
        session['feature_engineering'] = feature_engineering
        session['hypertuning'] = hypertuning
        session['num_trials'] = num_trials
        session['preset'] = preset
        session['time_limit'] = time_limit
        
        main_tf_content = f"""
terraform {{
  backend "gcs" {{
    bucket  = "{session['uuid_str']}"
    prefix  = "terraform/state"
  }}
}}

provider "google" {{
  region = var.region
  # Define the credentials using a local-exec provisioner
  # This will execute the command and use its output as the credential
}}

# Data source to fetch the default service account
data "google_compute_default_service_account" "default" {{
  project = var.project_id
}}

resource "google_pubsub_topic" "topic" {{
  name = "{'topic-'+session['uuid_str']}"
  project = "{PROJECT_ID}"

  
}}

resource "google_pubsub_subscription" "subscription" {{
  name = "{'topic-sub-'+session['uuid_str']}"
  topic = google_pubsub_topic.topic.id
  project = "{PROJECT_ID}"
}}

""" 
        with open('main.tf', 'w') as f:
            f.write(main_tf_content)

        return redirect(url_for('summary'),code=301)
    train_form = session.get('train_form', {})
    
    return render_template('define_train.html',completed_steps=session["completed_steps"], train_form=train_form)

@app.route('/summary', methods=['GET', 'POST'])
@jwt_required
def summary():
    
    if session["completed_steps"]["define_train"]:
        pass
    else:
        flash("Please complete define train step first. Use next button for next step.",category="warning")
        return redirect(url_for("define_train"), code=302)
    
    jwt_token = request.cookies.get('jwt_token')

    if not jwt_token:
        if app.config["DEBUG"]:
            print("JWT token not present, redirect to register URL")
        return redirect(register_url, code=302)

    try:
        if app.config["DEBUG"]:
            print("Check JWT token and extract user ID")
        
            print(session.get('user_email'),session.get('user_app_pass'))
        payload = verify_jwt_token(jwt_token)
        if app.config["DEBUG"]:
            print(payload['user_email'],payload['user_app_pass'] )
        assert (session.get('user_email'),session.get('user_app_pass')) == (payload['user_email'],payload['user_app_pass'])   # Function to decode JWT token and get user ID
    except Exception as e:
        if app.config["DEBUG"]:
            print("JWT token is invalid, redirect to register URL")
        return redirect(register_url, code=302)

    user_email = session.get('user_email')
    if user_email not in ids_370 + ids_371 + ids_372 + ids_814:
        if app.config["DEBUG"]:
            print("User ID not found in ids_372, redirect to register URL")
        return redirect(register_url, code=302)
    session["completed_steps"]['summary'] = True
    if request.method == 'POST':
        session["completed_steps"]['summary'] = True
        # Trigger training process
        
        return redirect(url_for('start_training'),code=301)

    return render_template('summary.html', session=session,completed_steps=session["completed_steps"])

@app.route('/start_training', methods=['GET','POST'])
@jwt_required
def start_training():
    global uuid_str2
    uuid_str2 = session['uuid_str']
    
    # Initialize a new queue for the current job UUID
    if uuid_str2 not in job_message_queues:
        job_message_queues[uuid_str2] = queue.Queue()
        if app.config["DEBUG"]:
            print(f"Queue created for job UUID: {uuid_str2}")

    jwt_token = request.cookies.get('jwt_token')

    if not jwt_token:
        if app.config["DEBUG"]:
            print("JWT token not present, redirect to register URL")
        return redirect(register_url, code=302)

    try:
        if app.config["DEBUG"]:
            print("Check JWT token and extract user ID")
        
            print(session.get('user_email'),session.get('user_app_pass'))
        payload = verify_jwt_token(jwt_token)
        if app.config["DEBUG"]:
            print(payload['user_email'],payload['user_app_pass'] )
        assert (session.get('user_email'),session.get('user_app_pass')) == (payload['user_email'],payload['user_app_pass'])   # Function to decode JWT token and get user ID
    except Exception as e:
        if app.config["DEBUG"]:
            print("JWT token is invalid, redirect to register URL")
        return redirect(register_url, code=302)

    user_email = session.get('user_email')
    if user_email not in ids_370 + ids_371 + ids_372 + ids_814:
        if app.config["DEBUG"]:
            print("User ID not found in ids_372, redirect to register URL")
        return redirect(register_url, code=302)
        

    if request.method == 'POST':
        
        def pubsub():
            # Create a Pub/Sub subscriber client
            subscriber = pubsub_v1.SubscriberClient()
            subscription_name = subscriber.subscription_path(PROJECT_ID, "topic-sub-"+uuid_str2)
            
            def callback(message):

                
                log = message.data.decode('utf-8')
                print(log)
                job_message_queues[uuid_str2].put(log)
                message.ack()
                
            
            future = subscriber.subscribe(subscription_name, callback)
            
            # Run the subscriber in a blocking loop
            try:
                
                future.result()
            except Exception as e:
                print(f"Listening for messages on {subscription_name} threw an exception: {e}.")
        
        sed_maintf = ["sed","-i" ,f's/"PROJECT_ID"/"{PROJECT_ID}"/', 'main.tf']
        mkdir_ = ["mkdir",session['uuid_str']]
        cp_file0 = ["cp","main.tf", f"./{session['uuid_str']}/"]
        cp_file1 = ["cp","variables.tf", f"./{session['uuid_str']}/"]
        terraform_commands_0 = ["terraform", f"-chdir=./{session['uuid_str']}/", "init"]
        terraform_commands_0_except = ["terraform", f"-chdir=./{session['uuid_str']}/", "init","-reconfigure"]
        terraform_commands_1 = ["terraform", f"-chdir=./{session['uuid_str']}/", "plan"]
        terraform_commands_2 = ["terraform", f"-chdir=./{session['uuid_str']}/", "apply","-auto-approve"]

        try:
            terraform_vm = [sed_maintf,mkdir_,cp_file0,cp_file1,terraform_commands_0, terraform_commands_1, terraform_commands_2]
            for i,command in enumerate(terraform_vm):
                if i==0:
                    try:
                        subprocess.check_call(command)
                    except:
                        subprocess.check_call(terraform_commands_0_except)
                elif i==1:
                    try:
                        subprocess.check_call(command)
                    except:
                        if app.config["DEBUG"]:
                            print("folder exist:", session['uuid_str'])
                else:
                    subprocess.check_call(command)
        except Exception as e:
            flash("Defined vm specs not available in selected zone. Especially check vm type.")
            flash("Check vm name. It should be chars between [-a-z0-9]{0,61}[a-z0-9]")
            return redirect(url_for("define_vm"),code=302)

        ## start messaging service
        threading.Thread(target=pubsub, daemon=True).start()
        if app.config["DEBUG"]:
            print("messaging service started")

        ## add vm to main.tf
        main_tf_content = f"""
resource "google_compute_instance" "vm_instance" {{

  project      = var.project_id
  name         = var.machine_name
  zone         = var.zone
  machine_type = var.machine_type
  boot_disk {{
    device_name = var.disk_name
    initialize_params {{
        
      image = var.image_type
      size  = var.disk_size
      type  = var.disk_type

    }}

  }}

  network_interface {{
    network = "default"

    access_config {{
      // Ephemeral public IP
    }}
  }}
  
  metadata = {{
    train_data = "{session['train_data_path']}"
    test_data = "{session['test_data_path']}"
    label = "{session['label']}"
    models_name = "{session['model_name']}"
    eval_metric = "{session['eval_metric']}"
    prediction = "{session['prediction']}"
    problem_type = "{session['problem_type']}"
    split_type = "{session['split_type']}"
    PREPROCESS = "{session['preprocess']}"
    basic_features_engineering = "{session['feature_engineering']}"
    PRESET = "{session['preset']}"
    time_limit = "{session['time_limit']}"
    hypertune = "{session['hypertuning']}"
    hyperparameter_num_trials = "{session['num_trials']}"
    load_predictor = "{session['load_predictor']}"
    load_predictor_path = "{session['load_predictor_path']}"
    pubsub_topic = "{"topic-"+session['uuid_str']}"
    project_id = "{PROJECT_ID}"
    uuid_str = "{session['uuid_str']}"
    
  }}


  service_account {{
    # Google recommends custom service accounts that have cloud-platform scope and permissions granted via IAM Roles.
    email  = "${{var.project_number}}-compute@developer.gserviceaccount.com"
    scopes = ["cloud-platform"]
  }}
tags = ["http-server","https-server"]

}}

"""
        # append vm to main.tf
        with open('main.tf', 'a') as f:
            f.write(main_tf_content)

        # create vm 
        try:
            terraform_vm = [ cp_file0,terraform_commands_1, terraform_commands_2]
            for i,command in enumerate(terraform_vm):
                if i==0:
                    try:
                        subprocess.check_call(command)
                    except:
                        subprocess.check_call(terraform_commands_0_except)
                else:
                    subprocess.check_call(command)
        except Exception as e:
            flash("Defined vm specs not available in selected zone. Especially check vm type.")
            flash("Check vm name. It should be chars between [-a-z0-9]{0,61}[a-z0-9]")
            return redirect(url_for("define_vm"),code=302)

        # TODO: add start time here for am_users_jobs

        start_time = (db.session.execute(text("SELECT NOW() as now")).first())[0]
        machine_type = session['machine_type']
        disk_type = session['disk_type']
        new_job = AmUsersJobs(
        user_id=session['user_id'],
        uuid=session['uuid_str'],
        start_time=start_time,
        status='running',
        machine_type=machine_type,  # Replace with session['machine_type'] if dynamic
        disk_type=disk_type,  # Replace with session['disk_type'] if dynamic
        disk_size = session["disk_size"],
        job_name = session['job_name'],
        model_name = session['model_name'],
        label = session["label"],
        problem_type = session["problem_type"],
        evaluation_metric = session['eval_metric'],
        prediction_type = session["prediction"],
        split_type = session["split_type"],
        preprocess = session["preprocess"],
        feature_engineering = session["feature_engineering"]
        )

        db.session.add(new_job)
        db.session.commit()
        
        
        
        return redirect(url_for('train_logs'),code=301)
        
    return "This route only supports POST requests."

@app.route('/train_logs_stream', methods=['GET'])
def train_logs_stream():
    # Get the current job UUID from the session
    job_uuid = session['uuid_str']
    
    def generate():
        while True:
            message = job_message_queues[job_uuid].get()  # Blocking get call on the correct queue
            yield f"data: {message}\n\n"
    
    return Response(generate(), mimetype='text/event-stream')

@app.route('/train_logs', methods=['GET', 'POST'])
@jwt_required
def train_logs():
    if session["completed_steps"]["define_task"] and session["completed_steps"]["define_train"]:
        pass
    else:
        flash("Please start train job first to see logs. Use start training button for next step.", category="warning")
        return redirect(url_for("summary"), code=302)

    jwt_token = request.cookies.get('jwt_token')

    if not jwt_token:
        if app.config["DEBUG"]:
            print("JWT token not present, redirect to register URL")
        return redirect(register_url, code=302)

    try:
        if app.config["DEBUG"]:
            print("Check JWT token and extract user ID")
            print(session.get('user_email'), session.get('user_app_pass'))
        payload = verify_jwt_token(jwt_token)
        if app.config["DEBUG"]:
            print(payload['user_email'], payload['user_app_pass'])
        assert (session.get('user_email'), session.get('user_app_pass')) == (payload['user_email'], payload['user_app_pass']) # Function to decode JWT token and get user ID
    except Exception as e:
        if app.config["DEBUG"]:
            print("JWT token is invalid, redirect to register URL")
        return redirect(register_url, code=302)

    user_email = session.get('user_email')
    if user_email not in ids_370 + ids_371 + ids_372 + ids_814:
        if app.config["DEBUG"]:
            print("User ID not found in ids_372, redirect to register URL")
        return redirect(register_url, code=302)

    session["completed_steps"]['train_logs'] = True

    if request.method == "POST":
        job_uuid = session['uuid_str']
        if job_uuid in job_message_queues:
            del job_message_queues[job_uuid]

        try:
            # Create the expected log file name
            log_file_name = f"-{session['label']}-{session['model_name']}-{session['eval_metric']}-{session['problem_type']}-{session['split_type']}-{session['uuid_str']}.log"
            LOG = read_log_file_from_gcs(bucket_name=PROJECT_ID, log_file_name=log_file_name)
        except:
            flash("You didn't define all parameters for training. You need to define parameter first for all steps.", 'error')
            return redirect(url_for('define_vm'), code=302)
        # DELETE vm
        try:
            terraform_commands_0 = ["terraform", f"-chdir=./{session['uuid_str']}/", "plan"]
            terraform_commands_1 = ["terraform", f"-chdir=./{session['uuid_str']}/", "destroy", "-auto-approve"]
            terraform_vm = [terraform_commands_0, terraform_commands_1]
            for command in terraform_vm:
                subprocess.check_call(command)
        except:
            flash("You didn't start training and create vm so can't delete. First start training please.", 'error')
            return redirect(url_for('summary'), code=302)

        flash("VM deleted successfully")

        if LOG:
            flash(f"Training log downloaded, check your Download folder. Ends with uuid: {session['uuid_str']}.")

            start_time = (db.session.query(AmUsersJobs.start_time).filter(AmUsersJobs.uuid == session['uuid_str']).first())[0]            
            # TODO: add success here am_users_jobs
            
            # # add success here am_users_jobs
            end_time = (db.session.execute(text("SELECT NOW() as now")).first())[0]
            delta = end_time - start_time
            delta_to_hours = delta.seconds / (60 * 60)
            cost_vm = vm_cost(session["machine_type"], session["disk_type"], delta_to_hours, session["disk_size"])

            # Update the job record with end_time, status, and cost
            job_record = db.session.query(AmUsersJobs).filter(AmUsersJobs.uuid == session['uuid_str']).first()
            if job_record:
                job_record.end_time = end_time
                job_record.status = 'success'
                job_record.cost = cost_vm
                job_record.disk_size = session["disk_size"]
                job_record.label = session["label"]
                job_record.problem_type = session["problem_type"]
                job_record.evaluation_metric = session['eval_metric']
                job_record.prediction_type = session["prediction"]
                job_record.split_type = session["split_type"]
                job_record.preprocess = session["preprocess"]
                job_record.feature_engineering = session["feature_engineering"]

            db.session.commit()
        else:
            # TODO: add cancelled here am_users_jobs
            # # add success here am_users_jobs
            start_time = (db.session.query(AmUsersJobs.start_time).filter(AmUsersJobs.uuid == session['uuid_str']).first())[0]
            end_time = (db.session.execute(text("SELECT NOW() as now")).first())[0]
            delta = end_time - start_time
            delta_to_hours = delta.seconds / (60 * 60)
            cost_vm = vm_cost(session["machine_type"], session["disk_type"], delta_to_hours, session["disk_size"])

            # Update the job record with end_time, status, and cost
            job_record = db.session.query(AmUsersJobs).filter(AmUsersJobs.uuid == session['uuid_str']).first()
            if job_record:
                job_record.end_time = end_time
                job_record.status = 'cancel'
                job_record.cost = cost_vm
                job_record.disk_size = session["disk_size"]
                job_record.label = session["label"]
                job_record.problem_type = session["problem_type"]
                job_record.evaluation_metric = session['eval_metric']
                job_record.prediction_type = session["prediction"]
                job_record.split_type = session["split_type"]
                job_record.preprocess = session["preprocess"]
                job_record.feature_engineering = session["feature_engineering"]

            db.session.commit()

        return render_template("train_logs.html", completed_steps=session["completed_steps"], LOGS=LOG)

    return render_template("train_logs.html", completed_steps=session["completed_steps"], LOGS=None)

@app.route('/logout')
def logout():
    # Clear the session
    session.clear()
    
    # Create a response object and clear cookies
    response = make_response(redirect(url_for('index')))
    response.set_cookie('jwt_token', '', expires=0)
    
    return response

if __name__ == "__main__":
    connect_to_db_with_retry(app)
