# GenAiDeepLabs Project

This repository contains the code and infrastructure setup for the GenaIDeepLabs project, which includes multiple applications deployed using Flask and Google Cloud.

## Project Links
- [Main Website](https://genaideeplabs.com)
- [LinkedIn](https://www.linkedin.com/in/mustafaksr/)
- [Project Details](https://excalidraw.com/#json=Sim6EuNZgtowlZ0YFW154,1bkzghBKrvDoxzlPC1Bn2w)

## Overview
Project Overview:
The project hosted on GenaideePlabs demonstrates expertise in API Development, Flask, Python, Front-End and Back-End Web Development, AI, Cloud-Native Architecture, Cloud Computing, Cloud Storage, Cloud Applications, and Terraform. It features three applications (AutoML, Chatbot, and SearchDocs) running on Docker Compose within an internal network, with the web server on an e2-standard-2 VM.
AutoML
The AutoML Application utilizes AutoGluon for machine learning tasks (regression, multiclass, binary), allowing users to configure VMs, data, tasks, and training options. It manages Terraform state and data storage with unique UUIDs, supporting features like train/test split, k-fold splits, hyperparameter tuning, preprocessing, batch prediction, and model loading. VM creation and job environment are managed via Terraform, tracking costs based on usage metrics.
url: automl.genaideeplabs.com
Chatbot
The Chatbot Application integrates a Gemini API backend for user-friendly query management, enabling storage, copy, and download per session to enhance interaction efficiency.
url backendgemini.genaideeplabs.com
SearchDocs
The SearchDocs Application employs Vertex AI's agent builder for search documents, facilitating advanced document search and synthesis. Users upload documents to Google Cloud Storage, query interactively, and receive relevant results with citations, enhancing document management and information retrieval.
url: searchdocument.genaideeplabs.com
CI/CD
The CI/CD Pipeline includes triggers for AutoML, creating new vm image for automl train app, Gemini Chatbot, and SearchDocument, ensuring automated and reliable deployment across the development lifecycle.
Tech Stack: 
Python, Flask (front-end, back-end), MySQL, Google Cloud (Cloud Storage, AI, Terraform), PHP (specific shortcodes), WordPress (membership, authentication plugins), Apache2.

This setup showcases robust integration for AI applications and efficient cloud-native development practices.

The GenaIDeepLabs project is designed to showcase various AI-powered applications using modern cloud infrastructure. The primary components of the project are:

- **AutoML Application**: An application leveraging AutoGluon for automated machine learning tasks.
- **Chatbot Application**: A chatbot powered by the Gemini API backend.
- **SearchDocs Application**: An application utilizing Vertex AI for enhanced document search and synthesis capabilities.
- **WordPress Site**: The main website hosted on Google Cloud with CI/CD integration.

## Project Infrastructure
The project infrastructure consists of the following components:

### Applications

- [automl](automl/)
- [chatbot](chatbot/)
- [forecast](forecast/)
- [question_document](question_document/)
- [question_table](question_table/)
- [searchdoc](searchdoc/)

### CI/CD Pipeline
The CI/CD pipeline is configured using Google Cloud Build and Terraform. There are four triggers:

1. **automl**             [automl](automl/)
2. **chatbot**            [chatbot](chatbot/)  
3. **forecast**           [forecast](forecast/)  
4. **question_document**  [question_document](question_table/)       
5. **question_table**     [question_table](question_table/)     
6. **searchdoc**          [searchdoc](searchdoc/)  
7. **wordpres-site**    



## Repository Structure
```plaintext
.
├── automl
│   ├── app
│   ├── LICENSE
│   ├── README.md
│   └── train
├── chatbot
│   ├── app
│   ├── LICENSE
│   └── README.md
├── forecast
│   ├── app.py
│   ├── cloudbuild.yaml
│   ├── data
│   ├── Dockerfile
│   ├── LICENSE
│   ├── README.md
│   └── requirements.txt
├── LICENSE
├── question_document
│   ├── app.py
│   ├── cloudbuild.yaml
│   ├── Dockerfile
│   ├── models--impira--layoutlm-document-qa
│   ├── readme.md
│   └── requirements.txt
├── question_table
│   ├── app.py
│   ├── cloudbuild.yaml
│   ├── Dockerfile
│   ├── models--microsoft--tapex-large-finetuned-wtq
│   └── requirements.txt
├── README.md
├── searchdoc
│   ├── app
│   ├── images
│   ├── LICENSE
│   ├── README.md
│   └── terraform_test
└── wordpres-site
    ├── cloudbuild.yaml
    ├── cloud run
    ├── Dockerfile
    ├── index.html
    ├── index.html\015
    ├── index.nginx-debian.html
    ├── index.php
    ├── license.txt
    ├── phpmyadmin
    ├── Readme.excalidraw
    ├── readme.html
    ├── robots.txt
    ├── wp-activate.php
    ├── wp-admin
    ├── wp-blog-header.php
    ├── wp-comments-post.php
    ├── wp-config.php
    ├── wp-config-sample.php
    ├── wp-content
    ├── wp-cron.php
    ├── wp-includes
    ├── wp-links-opml.php
    ├── wp-load.php
    ├── wp-login.php
    ├── wp-mail.php
    ├── wp-settings.php
    ├── wp-signup.php
    ├── wp-trackback.php
    └── xmlrpc.php
```
