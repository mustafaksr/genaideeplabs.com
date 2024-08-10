# AutoML Application

## Application Structure:
```
├── app
│   ├── app.py
│   ├── auth.py
│   ├── cloudbuild.yaml
│   ├── data
│   ├── database.py
│   ├── Dockerfile
│   ├── __init__.py
│   ├── models.py
│   ├── prototype.ipynb
│   ├── requirements_frozen.txt
│   ├── requirements.txt
│   ├── routes.py
│   ├── static
│   │   └── style.css
│   ├── templates
│   │   ├── base.html
│   │   ├── define_data.html
│   │   ├── define_job.html
│   │   ├── define_task.html
│   │   ├── define_train.html
│   │   ├── define_vm.html
│   │   ├── index.html
│   │   ├── summary.html
│   │   └── train_logs.html
│   └── utils.py
├── LICENSE
├── README.md
└── train
    ├── cluodbuild.yaml
    ├── Dockerfile
    ├── requirements.txt
    ├── startup_script_ubuntu.sh
    └── train.py
```

## Overview
The AutoML app is a comprehensive machine learning tool designed to simplify the process of training, evaluating, and deploying machine learning models. It leverages AutoGluon for its machine learning tasks, providing support for regression, multiclass, and binary classification problems. The app offers functionalities for data preprocessing, hyperparameter tuning, model training, and batch prediction, all within an intuitive user interface.

## Tech Stack
- **Programming Language**: Python
- **Framework**: Flask (for both front-end and back-end)
- **Database**: MySQL
- **Cloud Platform**: Google Cloud
- **Infrastructure as Code**: Terraform

## Application Link
[AutoML App](https://genaideeplabs.com)

## Key Features
- **AutoGluon Integration**: Supports regression, multiclass, and binary tasks.
- **Training and Prediction**: Offers options to train new models and load previously trained models for predictions.
- **Data Splitting**: Supports train_test_split and k-fold cross-validation.
- **Hyperparameter Tuning**: Provides basic hyperparameter tuning options.
- **Feature Preprocessing**: Includes basic feature preprocessing capabilities.
- **Batch Prediction**: Allows users to load batch predictions and use trained models.

## How It Works
- **User Inputs**: Users define the VM, data, task, and training options. The app assigns a unique UUID for each training job.
- **Terraform Integration**: The UUID is used for Terraform state management and data storage. Terraform first creates a Pub/Sub topic and subscription for user-specific training jobs and later creates the VM.
- **VM Setup**: The app uses Terraform to create a VM with a specific image, setting environment variables for the systemd batch process to start the training job on the VM.
- **Training Process**: Once the VM is created, the system service starts the training job, which trains the model, saves the model, and logs the training process. The logs are streamed to the app for real-time monitoring.
- **Job Management**: Users can delete jobs after completion or continue them as needed. The app tracks job states (successful or canceled) and logs these in the database.
- **Cost Tracking**: The app tracks the cost of AutoML by monitoring training time, VM usage, disk usage, and persistent disk type. This information is processed and displayed to the user via the UI.
- **Security**: The app uses JWT session tokens and database membership controls for user authentication and authorization.

## User Workflow
1. **Access**: Users access the app via their email and application password.
2. **Define Specifications**: Users define VM, data, task, and training specifications.
3. **Confirmation and Initialization**: The app summarizes the user-defined specifications and, upon confirmation, creates a unique bucket for user-specific Terraform state, data, and predictions.
4. **Training VM Image**: This is only changed when training app changes like new app features are added. It uses system service to start the training job when the VM starts.
5. **Terraform Operations**: The app uses subprocesses with gsutil to create a UUID bucket for a specific session. This bucket is used for Terraform state, training data storage, and prediction data storage. Terraform creates Pub/Sub topics, subscriptions, and then training VMs.
6. **Training and Monitoring**: Once the VM is created, a system service starts the training job with the specified environment variables. The model is trained, saved, and logs are streamed for interactive monitoring.
7. **Post-Training Operations**: Users can use the saved model for batch predictions. If a job is successfully completed and deleted, the app automatically downloads and displays the logs.

## Logging
- **Python Logging**: Used for application logging.
- **Pub/Sub**: Used for streaming training logs.
