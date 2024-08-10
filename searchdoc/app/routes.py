from flask import jsonify, request, redirect, url_for, session, render_template, flash, make_response
from app import app
from auth import jwt_required, create_jwt_token, verify_jwt_token
from models import *
from database import db, connect_to_db_with_retry
import os, subprocess, json
from utils import *
import uuid
from sqlalchemy import text, desc

global ids_370, ids_371, ids_372, ids_814
ids_370, ids_371, ids_372, ids_814 = connect_to_db_with_retry(app, retries=5, delay=2)

register_url = os.getenv("register_url")
PROJECT_ID = os.getenv('project_id')
LOCATION = os.environ["location"]
ENGINE_ID= os.environ["engine_id"]
DATA_STORE_ID = os.environ["data_store_id"]
BUCKET_NAME = os.environ["bucket_name"]

# Global dictionary to hold message queues for each job UUID
job_message_queues = {}

@app.route('/', methods=['GET', 'POST'])
def index():

    session["completed_steps"] = {
        '_index': False,
        'se_names': False,
        'summary': False,
        'app': False,

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
            response = make_response(redirect(url_for('se_names'),code=301))
            response.set_cookie('jwt_token', token, httponly=True, samesite='Lax')
            session["completed_steps"]["_index"] = True
            
           

            return response
        else:
            if app.config["DEBUG"]:
                print("Redirect to registration page with 401 Unauthorized status code")
            return redirect(register_url, code=401)
    user_data_form = session.get('user_data_form', {})
    return render_template('index.html', completed_steps=session["completed_steps"],user_data_form=user_data_form)

@app.route('/se_names' ,methods=['GET','POST'])
@jwt_required
def se_names():
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
    
    job_names_uuid = db.session.query( SeUsersJobs.ds_display_name,SeUsersJobs.se_datastore_display_name,SeUsersJobs.data_store_id,SeUsersJobs.search_engine_id,SeUsersJobs.uuid,SeUsersJobs.status,SeUsersJobs.create_time).filter(SeUsersJobs.user_id==session["user_id"]).order_by(desc(SeUsersJobs.create_time)).all()
    #job_names_uuid = [x[0] for x in job_names_uuid]
    if app.config["DEBUG"]:
        print(job_names_uuid)

    if request.method == 'POST':
        
        # TODO: update load search engine
        try:
            data = request.form
            print("load_search_engine",data['load_search_engine'])
            data['load_search_engine']
            session['load_search_engine'] = True
            if data['load_search_engine_path'] =="":
                flash("You didn't select which model to load from table. First select load model from table then select load predictor.")
                return redirect(url_for("define_job"))
            session['load_search_engine_path'] = data['load_search_engine_path']
        except:
            session['load_search_engine'] = False
            session['load_search_engine_path'] = None
        print("load_search_engine",session['load_search_engine'])
        if session['load_search_engine']:
            se_features = db.session.query( SeUsersJobs.ds_display_name,
            SeUsersJobs.se_datastore_display_name,
            SeUsersJobs.data_store_id,
            SeUsersJobs.search_engine_id,
            SeUsersJobs.location
            ).filter(SeUsersJobs.user_id==session["user_id"]).filter(SeUsersJobs.uuid==session["load_search_engine_path"]).all()
            print("se_features",se_features)

            session["ds_display_name"] = se_features[0][0]
            session["se_datastore_display_name"] = se_features[0][1]
            session["ds_id"] = se_features[0][2]
            session["se_id"] = se_features[0][3]
            session["location"] = se_features[0][4]

        else:

            session['job_form_data'] = request.form.to_dict()
            session["completed_steps"]["se_names"] =True
            data = request.form
            session['ds_display_name'] = data['ds_display_name']
            session['se_datastore_display_name'] = data['se_datastore_display_name']

            session['ds_id'] = data['ds_id']
            session['se_id'] = data['se_id']
            session['location'] = data['location']
        

            ds_display_names = db.session.query( SeUsersJobs.ds_display_name).filter(SeUsersJobs.user_id==session["user_id"]).all()
            
            se_datastore_display_names = db.session.query( SeUsersJobs.se_datastore_display_name).filter(SeUsersJobs.user_id==session["user_id"]).all()
            

            # Generate a random UUID
            random_uuid = uuid.uuid4()
            # Convert UUID to string representation
            uuid_str = str(random_uuid)

            if app.config["DEBUG"]:
                print("uuid:",uuid_str)

            session['uuid_str'] = uuid_str

            ds_display_names = db.session.query( SeUsersJobs.ds_display_name).filter(SeUsersJobs.user_id==session["user_id"]).all()
            ds_display_names = [x[0] for x in ds_display_names]

            se_datastore_display_names = db.session.query( SeUsersJobs.se_datastore_display_name).filter(SeUsersJobs.user_id==session["user_id"]).all()
            se_datastore_display_names = [x[0] for x in se_datastore_display_names]

            if app.config["DEBUG"]:
                print("ds",ds_display_names)
                print("se",se_datastore_display_names)

            if session['ds_display_name'] in ds_display_names:
                flash(f"Data Store Name: {session['ds_display_name']} is not unique. Your previous job names are below Previous Jobs table.")
                return redirect(url_for('se_names'),code=301)
            
            if session['se_datastore_display_name'] in se_datastore_display_names:
                flash(f"Search Engine Name: {session['se_datastore_display_name']} is not unique. Your previous job names are below Previous Jobs table.")
                return redirect(url_for('se_names'),code=301)

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

            variable "location" {{
            description = "Zone of Virtual Machine"
            default = "{data['location']}"
            }}

            variable "ds_display_name" {{

            type = string
            description = "get from session"
            default = "{session['ds_display_name']}"

            
            }}
            variable "se_datastore_display_name" {{

            type = string
            description = "get from session"
            default = "{session['se_datastore_display_name']}"
            
            }}

            variable "se_engine_id" {{
            type = string
            description = "get from tf output"
            default = "{session['se_id']}"
            }}

            variable "ds_id" {{
            type = string
            description = "get from tf output"
            default = "{session['ds_id']}"

            }}

            """

            with open('variables.tf', 'w') as f:
                f.write(variables_tf_content)

            #create buckets for uuid str and terraform state
            try:
                # create bucket for remote terraform state
                subprocess.check_call(["gsutil","mb",f"gs://{session['uuid_str']}"])
                # label to bucket app:automl-bucket
                subprocess.check_call(["gsutil","label", "ch" ,"-l","app:searchdocument-bucket" ,f"gs://{session['uuid_str']}"])
                
                if app.config["DEBUG"]:
                    print("gcs created." , "uuid:",session['uuid_str'])
            except:pass

            main_tf_content = f"""
    terraform {{
    backend "gcs" {{
        bucket  = "{session['uuid_str']}"
        prefix  = "terraform/state"
    }}
    }}

    provider "google" {{
    project = var.project_id
    credentials = "creds.json"

    }}

    resource "google_discovery_engine_data_store" "basic" {{
    location                    = var.location
    data_store_id               = var.ds_id
    display_name                = var.ds_display_name
    industry_vertical           = "GENERIC"
    content_config              = "CONTENT_REQUIRED"
    solution_types              = ["SOLUTION_TYPE_SEARCH"]
    create_advanced_site_search = false
    project = var.project_id
    }}
    resource "google_discovery_engine_search_engine" "basic2" {{
    engine_id      = var.se_engine_id
    collection_id  = "default_collection"
    location       = google_discovery_engine_data_store.basic.location
    display_name   = var.se_datastore_display_name
    data_store_ids = [google_discovery_engine_data_store.basic.data_store_id]
    search_engine_config {{
    }}
    project = var.project_id
    }}

    # Output block for data_store_id
    output "data_store_id" {{
    value = google_discovery_engine_data_store.basic.id
    }}

    # Output block for search_engine_id
    output "search_engine_id" {{
    value = google_discovery_engine_search_engine.basic2.id
    }}

    """ 
            with open('main.tf', 'w') as f:
                f.write(main_tf_content)


        return redirect(url_for('summary'),code=301)


    job_form_data = session.get('job_form_data', {})
    return render_template('se_names.html', completed_steps=session["completed_steps"], job_form_data=job_form_data,job_names_uuid=job_names_uuid)

@app.route('/summary', methods=['GET', 'POST'])
@jwt_required
def summary():
    
    if session["completed_steps"]["se_names"]:
        pass
    else:
        flash("Please complete define train step first. Use next button for next step.",category="warning")
        return redirect(url_for("se_names"), code=302)
    
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
        if session["load_search_engine"]:
            session["data_store_id"] = session["ds_id"]
            session["search_engine_id"] = session["se_id"]
            print("ids:",session["data_store_id"],session["search_engine_id"])
        else:
            sed_maintf = ["sed","-i" ,f's/"PROJECT_ID"/"{PROJECT_ID}"/', 'main.tf']
            mkdir_ = ["mkdir",session['uuid_str']]
            cp_file0 = ["cp","main.tf", f"./{session['uuid_str']}/"]
            cp_file1 = ["cp","variables.tf", f"./{session['uuid_str']}/"]
            cp_file2 = ["gsutil","cp",f"gs://{PROJECT_ID}/cred/creds.json", f"./{session['uuid_str']}/"]
            terraform_commands_0 = ["terraform", f"-chdir=./{session['uuid_str']}/", "init"]
            terraform_commands_0_except = ["terraform", f"-chdir=./{session['uuid_str']}/", "init","-reconfigure"]
            terraform_commands_1 = ["terraform", f"-chdir=./{session['uuid_str']}/", "plan"]
            terraform_commands_2 = ["terraform", f"-chdir=./{session['uuid_str']}/", "apply","-auto-approve"]

            try:
                terraform_vm = [sed_maintf,mkdir_,cp_file0,cp_file1,cp_file2,terraform_commands_0, terraform_commands_1, terraform_commands_2]
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
                    elif i==7:
                        try:
                            # Run the Terraform command and capture the output
                            output = subprocess.check_output(command, stderr=subprocess.STDOUT)
                            output_str = output.decode('utf-8')
                            
                            print("Terraform apply output:")
                            print(output_str)

                            # Run the Terraform output command to get the outputs in JSON format
                            terraform_output_command = ["terraform", f"-chdir=./{session['uuid_str']}/", "output", "-json"]
                            output_json = subprocess.check_output(terraform_output_command, stderr=subprocess.STDOUT)
                            output_data = json.loads(output_json.decode('utf-8'))

                            # Extract the desired outputs
                            data_store_id = output_data["data_store_id"]["value"]
                            search_engine_id = output_data["search_engine_id"]["value"]

                            print(f"Data Store ID: {data_store_id}")
                            print(f"Search Engine ID: {search_engine_id}")

                        except subprocess.CalledProcessError as e:
                            print(f"An error occurred while running Terraform: {e.output.decode('utf-8')}")
                    else:
                        subprocess.check_call(command)
            except Exception as e:
                flash("Defined vm specs not available in selected location. Especially check vm type.")
                flash("Check vm name. It should be chars between [-a-z0-9]{0,61}[a-z0-9]")
                return redirect(url_for("se_names"),code=302)

            session["data_store_id"] = data_store_id
            session["search_engine_id"] = search_engine_id
            print("ids:",data_store_id,search_engine_id)

            # TODO: add to db searche engine Created
            new_job = SeUsersJobs(
                user_id = session["user_id"],
                uuid = session["uuid_str"],
                ds_display_name = session["ds_display_name"],
                se_datastore_display_name = session["se_datastore_display_name"],
                data_store_id = session["ds_id"],
                search_engine_id = session["se_id"],
                status = "created"

            )
            db.session.add(new_job)
            db.session.commit()
            

        return redirect(url_for('search_app'),code=301)

    return render_template('summary.html', session=session,completed_steps=session["completed_steps"])

@app.route('/search_app', methods=['GET', 'POST'])
@jwt_required
def search_app():
    session["completed_steps"]['app'] = True
    if session["completed_steps"]["se_names"] and session["completed_steps"]["se_names"]:
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
        

        return render_template("search_app.html", completed_steps=session["completed_steps"])

    if session["load_search_engine"]:
        uuid_str = session["load_search_engine_path"]
        search_engine_id = session["search_engine_id"]
        search_engine_name = session["se_datastore_display_name"]
    else:
        uuid_str = session["uuid_str"]
        search_engine_id = session["search_engine_id"]
        search_engine_name = session["se_datastore_display_name"]

    return render_template("search_app.html", completed_steps=session["completed_steps"], se_options = [uuid_str,search_engine_id,search_engine_name])

@app.route('/delete_engine', methods=['POST'])
def delete_engine():
    if request.method == "POST":
        # DELETE se engine
        try:
            terraform_commands_0 = ["terraform", f"-chdir=./{session['uuid_str']}/", "plan"]
            terraform_commands_1 = ["terraform", f"-chdir=./{session['uuid_str']}/", "destroy", "-auto-approve"]
            terraform_vm = [terraform_commands_0, terraform_commands_1]
            for command in terraform_vm:
                subprocess.check_call(command)
        except:
            flash("You didn't create Search ENGINE.", 'error')
            return redirect(url_for('summary'), code=302)

        flash("Search ENGINE deleted successfully")
        # TODO: update se engine when deleted
        job_record = db.session.query(SeUsersJobs).filter(SeUsersJobs.uuid == session['uuid_str']).first()
        if job_record:
            job_record.status = 'deleted'
        db.session.commit()


        return render_template("search_app.html",completed_steps=session["completed_steps"])

    return render_template("search_app.html",completed_steps=session["completed_steps"])


@app.route('/search', methods=['POST'])
def search():
    
    project_id = PROJECT_ID
    location = session["location"]  # Values: LOCATION, "us", "eu"
    engine_id = session["se_id"]
    search_query = request.form['query']

    result = search_sample(project_id, location, engine_id, search_query)

    summary = result.summary.summary_with_metadata.summary

    summary_text = result.summary.summary_text
    citations = [f"[{i + 1}] {ref.title}" for i, ref in enumerate(result.summary.summary_with_metadata.references)]

    return render_template('search_app.html', summary=summary ,summary_text=summary_text, citations=citations, query=search_query)

@app.route('/purge_documents', methods=['POST'])
def purge_documents():
    # Call your purge function here
    project_id = PROJECT_ID
    location = session["location"]  # Values: LOCATION, "us", "eu"  
    data_store_id = session["ds_id"]
    branch = "default_branch"
    response = purge_all_documents_sample(project_id, location, data_store_id, branch)
    # Extract the purge count from the response
    purge_count = response
    return str(purge_count)  # Return purge count as text

@app.route('/list_documents', methods=['POST'])
def list_documents():
    project_id = PROJECT_ID
    location = session["location"]       
    data_store_id = session["ds_id"]
    document_uris = list_documents_sample(project_id, location, data_store_id)
    document_uris = [f"Listed documents counts : {len(document_uris)}"] + document_uris
    return jsonify(document_uris)

@app.route('/import_documents', methods=['POST'])
def import_docs():
    project_id = PROJECT_ID
    location = session["location"]   
    data_store_id = session["ds_id"]
    # Extracting the gcs_uris from the POST request
    gcs_uris = request.json.get('gcs_uris', [])
    result = import_documents(project_id, location, data_store_id, gcs_uris)
    return jsonify(result)

@app.route('/upload', methods=['POST'])
def upload():
    # Get the bucket name and files from the request
    bucket_name = session["uuid_str"] 
    files = request.files.getlist('files')

    # Save the files to a temporary directory
    temp_dir = 'books'  # Or any other temporary directory
    if not os.path.exists(temp_dir):
        os.makedirs(temp_dir)

    filenames = []
    for file in files:
        filename = os.path.join(temp_dir, file.filename)
        file.save(filename)
        filenames.append(filename)
    print(filenames)

    # Upload the files to the specified bucket
    upload_many_blobs_with_transfer_manager(bucket_name, filenames)

    return 'Files uploaded successfully GCS bucket!'

@app.route('/list_documents_gcs', methods=['POST'])
def list_blobs():
    
    bucket_name = session["uuid_str"] 
    document_uris = list_blobs_bucket(bucket_name)
    document_uris = [f"Total documents counts in bucket : {len(document_uris)}"] + document_uris
    return jsonify(document_uris)

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
