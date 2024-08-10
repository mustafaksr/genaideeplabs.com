import streamlit as st
from transformers import TapexTokenizer, BartForConditionalGeneration
import pandas as pd

model_dir = "models--microsoft--tapex-large-finetuned-wtq/snapshots/e7073dd4c5fcf2fe92c4c660c953575d6de2d1a6"
tokenizer = TapexTokenizer.from_pretrained(model_dir)
model = BartForConditionalGeneration.from_pretrained(model_dir)

# Streamlit app title
st.title("Table Question Answering")

# App description and instructions
st.write("""
This application allows you to upload a CSV file containing tabular data and ask questions about its content.
It uses a pre-trained NLP model to analyze the table and provide answers based on the data within the table.

### Instructions:
1. **Upload a CSV File**: Click on the "Choose a CSV file for data" button to select and upload a CSV file of the table you want to query.
2. **Enter Your Question**: Type your question in the text input field.
3. **Get Results**: Once both the CSV file and the question are provided, the model will process the table and display the answer.

**Note**: Ensure that the table is clear and properly formatted for accurate results.
""")

uploaded_file = st.file_uploader("Choose a CSV file for data", type="csv")
if uploaded_file:
    # Display the uploaded table
    table = pd.read_csv(uploaded_file)

    num_rows = st.text_input("Enter the number of rows to display:", "10", key="number")
    st.dataframe(table.head(int(num_rows)))

# Input field for question
question = st.text_input("Enter your question:")

# If both a file and question are provided
if uploaded_file and question:
    try:
        # Encode the table and question for the model
        encoding = tokenizer(table=table, query=question, return_tensors="pt")
        # Get the answer from the model
        outputs = model.generate(**encoding)
        result = tokenizer.batch_decode(outputs, skip_special_tokens=True)
        # Display the result
        st.write("**Question:**", question)
        st.write("**Result:**", result[0])

    except Exception as e:
        st.write("Error:", e)
else:
    st.write("Please upload a CSV file and enter your question.")
