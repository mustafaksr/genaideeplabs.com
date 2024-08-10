import streamlit as st
import pandas as pd
from bokeh.plotting import figure
from bokeh.models import HoverTool, ColumnDataSource
from bokeh.embed import components
import numpy as np
import matplotlib.pyplot as plt
import math
import pandas as pd
from autogluon.timeseries import TimeSeriesDataFrame, TimeSeriesPredictor
from io import BytesIO

# Title of the app
st.title('Simple Forecast App')

st.write("""
Welcome to the **Simple Forecast App**! This application enables you to load, view, and analyze time series data using AutoGluon's powerful forecasting capabilities. Follow the steps below to utilize the app effectively:
         
**Note:** Be sure that the correct columns are selected for training to ensure the forecasting app runs successfully.
1. **Load Data**: Upload your training and testing CSV files.
2. **View Data**: Specify the number of rows to display and sort the data by any column.
3. **Plot Data**: Generate line plots for your selected columns to visualize the trends.
4. **Setup Training Data**: Select the appropriate columns for ID and timestamp to prepare your time series data.
5. **AutoGluon Parameters**: Set parameters for prediction length, target column, quality presets, and time limit.
6. **Train Model**: AutoGluon will fit the model based on your inputs.
7. **View Results**: Examine the leaderboard and predictions, and download the results as CSV and charts as PNG.

This app is ideal for quickly setting up and evaluating time series forecasting models with minimal coding effort. Enjoy forecasting with ease!

You can download and use data as below Autogluon csv.

train:
```
https://autogluon.s3.amazonaws.com/datasets/timeseries/m4_hourly_subset/train.csv
```

test:         
```
https://autogluon.s3.amazonaws.com/datasets/timeseries/m4_hourly_subset/test.csv
```


""")

st.write("## Load Data")
# File uploader widget
uploaded_file = st.file_uploader("Choose a CSV file for train", type="csv")
uploaded_file2 = st.file_uploader("Choose a CSV file  for test", type="csv")
if (uploaded_file is not None) and (uploaded_file2 is not None):
    # Read the CSV file
    df = pd.read_csv(uploaded_file)
    df_test = pd.read_csv(uploaded_file2)
    df_test0 = df_test.copy()
    df0 = df.copy()
    bool_cols = [x for x in df.columns if df[x].dtype==bool]
    df[bool_cols] = df[bool_cols].astype(int)
    st.write("### View Data")
    # Input widget to specify the number of rows to display
    num_rows = st.text_input("Enter the number of rows to display:", "10",key="number")

    # Selectbox to choose the column to sort by
    sort_column = st.selectbox("Select column to sort by:", df.columns)

    # Buttons to sort the DataFrame
    sort_asc = st.button("Sort Ascending")
    sort_desc = st.button("Sort Descending")
    col1, col2 = st.columns(2)
    with col1:
        st.write("#### Train Data")
        # Sorting logic
        if sort_asc:
            df = df.sort_values(by=sort_column, ascending=True)
        elif sort_desc:
            df = df.sort_values(by=sort_column, ascending=False)

        try:
            num_rows = int(num_rows)
            if num_rows > len(df):
                num_rows = len(df)
            # Display the specified number of rows of the DataFrame
            st.write(f"Displaying the first {num_rows} rows of the CSV file:")
            st.dataframe(df.head(num_rows))
        except ValueError:
            st.write("Please enter a valid number.")
    with col2:
        st.write("#### Test Data")

        # Sorting logic
        if sort_asc:
            df_test0 = df_test0.sort_values(by=sort_column, ascending=True)
        elif sort_desc:
            df_test0 = df_test0.sort_values(by=sort_column, ascending=False)

        try:
            num_rows = int(num_rows)
            if num_rows > len(df_test0):
                num_rows = len(df_test0)
            # Display the specified number of rows of the DataFrame
            st.write(f"Displaying the first {num_rows} rows of the CSV file:")
            st.dataframe(df_test0.head(num_rows))
        except ValueError:
            st.write("Please enter a valid number.")

    # Scatter plot section
    st.write("### Train Line Plot")
    X = st.selectbox("Select X axis:",df.columns,index=1)
    Y = st.selectbox("Select Y axis:",df.columns,index=2)
    num_rows2 = st.text_input("Enter the number of rows to display:", "250",key="number2")
    data = (df.iloc[:int(num_rows2)][[X,Y]]).set_index(X)
    st.line_chart(data=data)
    st.write("## Setup Training Data")
    st.write("### Select id and timestamp")

    try:
        id_column = st.selectbox("Select id column:", df.columns, index= 0)
        
    except:
        id_column = st.selectbox("Select id column:", df.columns)
    try:
        timestamp_column = st.selectbox("Select timestamp column:", df.columns, index= 1)
        
    except:
        timestamp_column = st.selectbox("Select timestamp column:", df.columns)

    train_data = TimeSeriesDataFrame.from_data_frame(
    df0,
    id_column=id_column,
    timestamp_column=timestamp_column
)   
    st.write("### Created Train data")
    num_rows3 = st.text_input("Enter the number of rows to display:", "3",key="number3")
    st.dataframe(train_data.head(int(num_rows3)))

    st.write("## AutoGluon Parameters")
    st.write("### Set Parameters:")

    # Create four columns for the inputs
    _col1, _col2, _col3, _col4 = st.columns(4)

    # Place each input widget in a different column
    with _col1:
        quantile_levels = st.multiselect("Select quantiles:", [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9],default=[0.1,0.9])
        time_limit = st.number_input("Time Limit (seconds)(increase value for better result.):", min_value=1, value=90,max_value=240)

    with _col2:
        max_num_item_ids = st.selectbox("Select max items", [4, 6, 8])
        try:
            presets = st.selectbox("Select presets:", ["medium_quality", "high_quality", "best_quality"],index=0)
        except:
            presets = st.selectbox("Select presets:", ["medium_quality", "high_quality", "best_quality"])

    with _col3:
        max_history_length = st.selectbox("Select max window", [200, 300, 400])

        try:
            target_column = st.selectbox("Select target column:", df.columns,index=2)
        except:
            target_column = st.selectbox("Select target column:", df.columns)

    with _col4:
        prediction_length = st.number_input("Prediction Length:", min_value=1, value=48)





    predictor = TimeSeriesPredictor(
        prediction_length=prediction_length,
        path="autogluon-m4-hourly",
        target=target_column,
        eval_metric="MASE",)
    with st.spinner("Fitting the model..."):
        predictor.fit(
        train_data,
        presets=presets,
        time_limit=time_limit,
         )
    st.write("### AutoGluon Results:")
    st.write("AutoGluon LB:")
    predictions = predictor.predict(train_data)
    LB = predictor.leaderboard(df_test)
    st.dataframe(LB)
    
    # Plot chart with Streamlit
    st.write("AutoGluon Chart:")
    fig = predictor.plot(df_test, predictions, quantile_levels=[0.1, 0.9], max_history_length=max_history_length, max_num_item_ids=max_num_item_ids)
    st.pyplot(fig)


    st.write("### Predictions Data:")
    # Convert the pandas DataFrame to a CSV
    @st.experimental_memo
    def convert_df(_df):
        # IMPORTANT: Cache the conversion to prevent computation on every rerun
        return _df.to_csv().encode("utf-8")

    csv = convert_df(predictions)
    try:
        num_rows4 = st.text_input("Enter the number of rows to display:", "25",key="number4")
        st.dataframe(predictions.head(int(num_rows4)))
    except:pass
    st.write("### Download predictions data as CSV:")
    st.download_button(
        label="Download",
        data=csv,
        file_name="predictions.csv",
        mime="text/csv",
    )
    st.write("### Download predictions chart as PNG:")
    # Save the plot to a BytesIO buffer
    buf = BytesIO()
    fig.savefig(buf, format='png')
    buf.seek(0)

    # Add a download button
    st.download_button(
        label="Download plot as PNG",
        data=buf,
        file_name="plot.png",
        mime="image/png"
    )

    
else:
    st.write("Please upload a CSV file to see its contents.")






