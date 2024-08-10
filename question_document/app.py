import streamlit as st
from transformers import pipeline
from PIL import Image

model_dir = "models--impira--layoutlm-document-qa/snapshots/beed3c4d02d86017ebca5bd0fdf210046b907aa6"
# Load the NLP pipeline
nlp = pipeline(
    "document-question-answering",
    model=model_dir,
)

# Streamlit app title
st.title("Document Question Answering")

# App description and instructions
st.write("""
This application allows you to upload a document image and ask questions about its content. 
It uses a pre-trained NLP model to analyze the document and provide answers based on the text within the image.

### Instructions:
1. **Upload a Document**: Click on the "Upload a document image" button to select and upload an image file (PNG, JPG, or JPEG) of the document you want to query.
2. **Enter Your Question**: Type your question in the text input field.
3. **Get Results**: Once both the document and the question are provided, the model will process the document and display the answer.

**Note**: Ensure that the document is clear and legible for accurate results.
""")

# File uploader for document
uploaded_file = st.file_uploader("Upload a document image:", type=["png", "jpg", "jpeg"])
if uploaded_file:
    # Display the uploaded image
    img = Image.open(uploaded_file)
    st.image(img, caption="Uploaded Document", use_column_width=True)

# Input field for question
question = st.text_input("Enter your question:")

# If both a file and question are provided
if uploaded_file and question:
    try:
        # Save the uploaded file to a temporary location
        temp_file_path = f"/tmp/{uploaded_file.name}"
        img.save(temp_file_path)

        # Get the answer from the model
        result = nlp(temp_file_path, question)

        # Display the result
        st.write("**Question:**", question)
        st.write("**Result:**", result)

    except Exception as e:
        st.write("Error:", e)
else:
    st.write("Please upload a document image and enter your question.")
