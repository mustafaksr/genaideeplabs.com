
# Search and Conversation App a.k.a Agent Builder

This repository contains code for a search and conversation web application powered by Vertex AI's search and conversation features. Basically app, upload unstruced documents to GCS, store, list, delete documents and search in single page for specific usage. 

This project leverages Vertex AI's agent builder service, supporting RAG (Retrieval-Augmented Generation), to enhance document search and synthesis capabilities. The application allows users to upload documents to Google Cloud Storage (GCS) and store them in the agent builder's datastore. Users can interactively query these documents, asking questions related to the content. The app retrieves relevant results with citations, providing both the answer text and a citation list. This setup integrates advanced AI-powered document retrieval and interactive querying, facilitating efficient information retrieval and synthesis. Ideal for applications requiring robust document management and interactive information retrieval functionalities.

## Pre-requisites

Before running the application, ensure you have the following:

- **Google Cloud Platform Account:** You need a GCP account with billing enabled.
- **Python:** Ensure you have Python installed on your system.
- **Google Cloud SDK:** Install the Google Cloud SDK for managing your GCP resources.
- **Environment Variables:** You'll need to set up environment variables for your project ID, location, engine ID, and data store ID. These should be placed in a `.env` file in the root directory of the project. See the provided `.env.example` file for reference.
- **Search and Conversation App:** You'll need to create search and conversation app manually. Explained detailed how to create app in Setup steps 0.
- **Create a Google Cloud Storage Bucket:** You'll need to create Google Cloud Storage Bucket.

## Setup

0. **Create Google Cloud Storage Bucket:** Create a gcs bucket for search and conversation app.

    ```bash
    gsutil mb gs://"your-bucket-name"
    ```

1. **Create Search and Conversation App:** 

        1. Go to Agent Builder from google cloud console.
        2. Pick Search app
        3. Give a name your app and your company name (company name can be as "null" for example).
        4. Leave rest default and Click Continue.
        5. Click Create Data Store and Select Cloud Storage and pick your folder location, leave data type as unstructred.
        6. İf you have permission problem with selecting bucket folder, give Storage Legacy Object Reader permission to Viewers of project: "Your Project" from cloud storage permissions and then try select again folder.
        7. Give name your data store after selecting folder.
        8. Check your data store and Click create your app is ready.
        9. Note your data store id and app engine id to your .env file.


2. **Clone the Repository:** Clone this repository to your local machine.

    ```bash
    git clone https://github.com/mustafaksr/document-search-app-vertex-ai.git
    ```

3. **Install Dependencies:** Navigate to the project directory and install the Python dependencies.

    ```bash
    cd document-search-app-vertex-ai
    pip install -r requirements.txt
    ```
    
4. **Set Up Environment Variables:** Create a `.env` file in the root directory and populate it with your GCP project ID, location, engine ID, and data store ID.

    ```plaintext
    export project_id='your-project-id'
    export location='your-location'  
    export engine_id='your-engine-id'
    export data_store_id='your-data-store-id'
    export bucket_name='your-bucket-name'
    ```

5. **Run the Application:** Start the Flask web server.

    ```bash
    python webapp/app.py
    ```

    

6. **Access the Web App:** Open a web browser and navigate to `http://localhost:5000` to access the search and conversation app. Using Front-end, Add document GCS then store it data store and start search within documents using search widget.



## File Structure

The repository has the following structure:

- `.env`: Configuration file for environment variables.
- `README.md`: Instructions and documentation.
- `webapp/`: Directory containing the web application code.
    - `app.py`: Flask application script.
    - `templates/`: HTML templates for the web pages.
    - `utils.py`: Utility functions for interacting with Google Cloud services.

## Usage

- **Upload Documents:** Use the provided form to upload documents to a Google Cloud Storage bucket.
- **List Documents:** View the list of documents in the Google Cloud Storage bucket.
- **Add Documents:** Add documents by providing their GCS URIs.
- **Search:** Enter a search query to retrieve relevant information from the uploaded documents.
- **Purge Documents:** Remove all documents from the data store.



## License

This project is licensed under the [Apache License](LICENSE).


# Search Docs Application

## Application Structure:

```
├── app
│   ├── app.py
│   ├── auth.py
│   ├── cloudbuild.yaml
│   ├── database.py
│   ├── Dockerfile
│   ├── __init__.py
│   ├── models.py
│   ├── public
│   │   └── index.html
│   ├── requirements.txt
│   ├── routes.py
│   ├── server.js
│   ├── static
│   │   └── style.css
│   ├── templates
│   │   ├── base.html
│   │   ├── define_train.html
│   │   ├── index.html
│   │   ├── search_app.html
│   │   ├── se_names.html
│   │   └── summary.html
│   └── utils.py
├── LICENSE
└── README.md
```

## Overview
The Search Docs app is a powerful tool designed to enhance document search and synthesis capabilities using advanced AI techniques. Leveraging Vertex AI's agent builder service, it supports Retrieval-Augmented Generation (RAG) to provide highly accurate and contextually relevant search results. Users can upload documents, query their content interactively, and receive results with citations, making it ideal for robust document management and interactive information retrieval.

## Tech Stack
- **Programming Language:** Python, JavaScript
- **Framework:** Flask (for both front-end and back-end)
- **Database:** Google Cloud Datastore
- **Cloud Platform:** Google Cloud
- **Infrastructure as Code:** Terraform

## Application Link
[Search Docs App](#)

## Key Features
- **Vertex AI Integration:** Utilizes Vertex AI's agent builder service for enhanced document search and synthesis.
- **RAG Support:** Implements Retrieval-Augmented Generation to provide detailed search results with citations.
- **Document Management:** Allows users to upload documents to Google Cloud Storage and store them in the agent builder's datastore.
- **Interactive Querying:** Enables users to query documents interactively and retrieve relevant answers along with citation lists.
- **Unique UUID Usage:** Each user and document is assigned a unique UUID for efficient storage and management, similar to the AutoML app.

## How It Works
- **User Inputs:** Users access the app and upload their documents to Google Cloud Storage.
- **UUID Assignment:** The app assigns a unique UUID for each document, which is used for storage and management.
- **Vertex AI Integration:** The app integrates with Vertex AI's agent builder service to facilitate document retrieval and interactive querying.
- **Interactive Querying:** Users can interactively query the uploaded documents. The app retrieves relevant results with answer text and citations.
- **Search Widget:** The app uses a ready widget for search query integration, enhancing the user experience.

## User Workflow
1. **Access:** Users access the app and authenticate using their credentials.
2. **Document Upload:** Users upload documents to Google Cloud Storage.
3. **Querying:** Users can query the uploaded documents interactively. The app processes the queries and returns relevant results with citations.
4. **Result Display:** The app displays the search results along with citation lists, facilitating efficient information retrieval and synthesis.

*For now, we only demonstrate demo related injection molding documents.*
