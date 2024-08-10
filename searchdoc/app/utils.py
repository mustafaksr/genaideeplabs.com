from google.cloud import storage
from passlib.hash import phpass
import os
from typing import Optional
from google.api_core.client_options import ClientOptions
from google.cloud import discoveryengine
from typing import Any
from dotenv import load_dotenv
from google.cloud import discoveryengine_v1 as discoveryengine
load_dotenv()


LOCATION = os.environ["location"]
FLASK_DEBUG = os.getenv("FLASK_DEBUG")

def purge_all_documents_sample(project_id: str, location: str, data_store_id: str,branch: str) -> Any:
    client_options = (
            ClientOptions(api_endpoint=f"{location}-discoveryengine.googleapis.com")
            if location != LOCATION
            else None
        )

    # Create a client
    client = discoveryengine.DocumentServiceClient(client_options=client_options)

    # The full resource name of the search engine branch.
    # e.g. projects/{project}/locations/{location}/dataStores/{data_store_id}/branches/{branch}
    parent = client.branch_path(
        project=project_id,
        location=location,
        data_store=data_store_id,
        branch=branch,
    )

    # Initialize request argument(s)
    request = discoveryengine.PurgeDocumentsRequest(
        parent=parent,#f"projects/{project_id}/locations/{location}/collections/{collection}/dataStores/{data_store_id}/branches/{branch}",
        filter="*",
        force=True
    )

    operation = client.purge_documents(request=request)
    response = operation.result()
    print(response)
    return response


def list_documents_sample(project_id: str, location: str, data_store_id: str) -> Any:
    #  For more information, refer to:
    # https://cloud.google.com/generative-ai-app-builder/docs/locations#specify_a_multi-region_for_your_data_store
    client_options = (
        ClientOptions(api_endpoint=f"{location}-discoveryengine.googleapis.com")
        if location != LOCATION
        else None
    )

    # Create a client
    client = discoveryengine.DocumentServiceClient(client_options=client_options)

    # The full resource name of the search engine branch.
    # e.g. projects/{project}/locations/{location}/dataStores/{data_store_id}/branches/{branch}
    parent = client.branch_path(
        project=project_id,
        location=location,
        data_store=data_store_id,
        branch="default_branch",
    )

    response = client.list_documents(parent=parent)

    print(f"Documents in {data_store_id}:")
#     for result in response:
#         print(result)

    return [doc.content.uri for doc in response.documents]


def import_documents(
    project_id: str,
    location: str,
    data_store_id: str,
    gcs_uris: Optional[list] = None
) -> str:
    #  For more information, refer to:
    # https://cloud.google.com/generative-ai-app-builder/docs/locations#specify_a_multi-region_for_your_data_store
    client_options = (
        ClientOptions(api_endpoint=f"{location}-discoveryengine.googleapis.com")
        if location != LOCATION
        else None
    )

    # Create a client
    client = discoveryengine.DocumentServiceClient(client_options=client_options)

    # The full resource name of the search engine branch.
    # e.g. projects/{project}/locations/{location}/dataStores/{data_store_id}/branches/{branch}
    parent = client.branch_path(
        project=project_id,
        location=location,
        data_store=data_store_id,
        branch="default_branch",
    )

    if gcs_uris:
        request = discoveryengine.ImportDocumentsRequest(
            parent=parent,
            gcs_source=discoveryengine.GcsSource(
                input_uris=gcs_uris, data_schema="content"
            ),
            # Options: `FULL`, `INCREMENTAL`
            reconciliation_mode=discoveryengine.ImportDocumentsRequest.ReconciliationMode.INCREMENTAL,
        )


    # Make the request
    operation = client.import_documents(request=request)

    print(f"Waiting for operation to complete: {operation.operation.name}")
    response = operation.result()

    # Once the operation is complete,
    # get information from operation metadata
    metadata = discoveryengine.ImportDocumentsMetadata(operation.metadata)

    # Handle the response
    print(response)
    print(metadata)

    return f"Succesfully added documents counts : {metadata.success_count}"



def upload_many_blobs_with_transfer_manager(
    bucket_name, filenames, source_directory="", workers=8
):
    """Upload every file in a list to a bucket, concurrently in a process pool.

    Each blob name is derived from the filename, not including the
    `source_directory` parameter. For complete control of the blob name for each
    file (and other aspects of individual blob metadata), use
    transfer_manager.upload_many() instead.
    """

    # The ID of your GCS bucket
    # bucket_name = "your-bucket-name"

    # A list (or other iterable) of filenames to upload.
    # filenames = ["file_1.txt", "file_2.txt"]

    # The directory on your computer that is the root of all of the files in the
    # list of filenames. This string is prepended (with os.path.join()) to each
    # filename to get the full path to the file. Relative paths and absolute
    # paths are both accepted. This string is not included in the name of the
    # uploaded blob; it is only used to find the source files. An empty string
    # means "the current working directory". Note that this parameter allows
    # directory traversal (e.g. "/", "../") and is not intended for unsanitized
    # end user input.
    # source_directory=""

    # The maximum number of processes to use for the operation. The performance
    # impact of this value depends on the use case, but smaller files usually
    # benefit from a higher number of processes. Each additional process occupies
    # some CPU and memory resources until finished. Threads can be used instead
    # of processes by passing `worker_type=transfer_manager.THREAD`.
    # workers=8

    from google.cloud.storage import Client, transfer_manager

    storage_client = Client()
    bucket = storage_client.bucket(bucket_name)

    results = transfer_manager.upload_many_from_filenames(
        bucket, filenames, source_directory=source_directory, max_workers=workers
    )

    for name, result in zip(filenames, results):
        # The results list is either `None` or an exception for each filename in
        # the input list, in order.

        if isinstance(result, Exception):
            print("Failed to upload {} due to exception: {}".format(name, result))
        else:
            print("Uploaded {} to {}.".format(name, bucket.name))

def list_blobs_bucket(bucket_name):
    """Lists all the blobs in the bucket."""
    # bucket_name = "your-bucket-name"

    storage_client = storage.Client()

    # Note: Client.list_blobs requires at least package version 1.17.0.
    blobs = storage_client.list_blobs(bucket_name)
    filtered_blobs = []
    gcs_urls = []
    # Note: The call returns a response only when the iterator is consumed.
    for blob in blobs:
        if blob.name.startswith("books"):
#             print(blob.name)
            filtered_blobs.append(blob)
            gcs_urls.append("gs://"+blob.public_url.split("https://storage.googleapis.com/")[1])
    return gcs_urls


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

def search_sample(
    project_id: str,
    location: str,
    engine_id: str,
    search_query: str,
) -> discoveryengine.SearchResponse:
    client_options = (
        ClientOptions(api_endpoint=f"{location}-discoveryengine.googleapis.com")
        if location != LOCATION
        else None
    )

    client = discoveryengine.SearchServiceClient(client_options=client_options)

    serving_config = f"projects/{project_id}/locations/{location}/collections/default_collection/engines/{engine_id}/servingConfigs/default_config"

    content_search_spec = discoveryengine.SearchRequest.ContentSearchSpec(
        snippet_spec=discoveryengine.SearchRequest.ContentSearchSpec.SnippetSpec(
            return_snippet=True
        ),
        summary_spec=discoveryengine.SearchRequest.ContentSearchSpec.SummarySpec(
            summary_result_count=5,
            include_citations=True,
            ignore_adversarial_query=True,
            ignore_non_summary_seeking_query=True,
            model_prompt_spec=discoveryengine.SearchRequest.ContentSearchSpec.SummarySpec.ModelPromptSpec(
                preamble="Given the conversation between a user and a helpful assistant and some search results, create a final answer for the assistant. The answer should use all relevant information from the search results, not introduce any additional information, and use exactly the same words as the search results when possible. The assistant's answer should be no more than 20 sentences. The user is an expert who has an in-depth understanding of the subject matter. The assistant should answer in a technical manner that uses specialized knowledge and terminology when it helps answer the query."
            ),
            model_spec=discoveryengine.SearchRequest.ContentSearchSpec.SummarySpec.ModelSpec(
                version="preview",
            ),
        ),
    )

    request = discoveryengine.SearchRequest(
        serving_config=serving_config,
        query=search_query,
        page_size=10,
        content_search_spec=content_search_spec,
        query_expansion_spec=discoveryengine.SearchRequest.QueryExpansionSpec(
            condition=discoveryengine.SearchRequest.QueryExpansionSpec.Condition.AUTO,
        ),
        spell_correction_spec=discoveryengine.SearchRequest.SpellCorrectionSpec(
            mode=discoveryengine.SearchRequest.SpellCorrectionSpec.Mode.AUTO
        ),
    )

    response = client.search(request)
    return response