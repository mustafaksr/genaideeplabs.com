# Simple Forecast App

Welcome to the **Simple Forecast App**! This application enables you to load, view, and analyze time series data using AutoGluon's powerful forecasting capabilities. Follow the steps below to utilize the app effectively:

## Features

1. **Load Data**: Upload your training and testing CSV files.
2. **View Data**: Specify the number of rows to display and sort the data by any column.
3. **Plot Data**: Generate line plots for your selected columns to visualize the trends.
4. **Setup Training Data**: Select the appropriate columns for ID and timestamp to prepare your time series data.
5. **AutoGluon Parameters**: Set parameters for prediction length, target column, quality presets, and time limit.
6. **Train Model**: AutoGluon will fit the model based on your inputs.
7. **View Results**: Examine the leaderboard and predictions, and download the results as CSV and charts as PNG.

This app is ideal for quickly setting up and evaluating time series forecasting models with minimal coding effort. Enjoy forecasting with ease!

## Application Structure

```plaintext
├── app.py
├── cloudbuild.yaml
├── data
│   ├── test.csv
│   └── train.csv
├── Dockerfile
├── LICENSE
├── README.md
├── requirements.txt
└── .streamlit
    └── config.toml
```

## Getting Started

### Prerequisites

- Python 3.9+
- Streamlit
- AutoGluon

### Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/yourusername/simple-forecast-app.git
    cd simple-forecast-app
    ```

2. Install the required packages:
    ```bash
    pip install -r requirements.txt
    ```

3. Run the application:
    ```bash
    streamlit run app.py
    ```

### Usage

1. **Load Data**: Upload your training and testing CSV files.
2. **View Data**: Specify the number of rows to display and sort the data by any column.
3. **Plot Data**: Generate line plots for your selected columns to visualize the trends.
4. **Setup Training Data**: Select the appropriate columns for ID and timestamp to prepare your time series data.
5. **AutoGluon Parameters**: Set parameters for prediction length, target column, quality presets, and time limit.
6. **Train Model**: AutoGluon will fit the model based on your inputs.
7. **View Results**: Examine the leaderboard and predictions, and download the results as CSV and charts as PNG.

## Example Data

You can use the following example data to test the application:

- [Training Data](https://autogluon.s3.amazonaws.com/datasets/timeseries/m4_hourly_subset/train.csv)
- [Testing Data](https://autogluon.s3.amazonaws.com/datasets/timeseries/m4_hourly_subset/test.csv)

## Deployment

### Docker

To build and run the application using Docker, follow these steps:

1. Build the Docker image:
    ```bash
    docker build -t simple-forecast-app .
    ```

2. Run the Docker container:
    ```bash
    docker run -p 8080:8080 simple-forecast-app
    ```

### Google Cloud Build

Use the `cloudbuild.yaml` file to deploy the application to Google Cloud Run:

1. Trigger a build on Google Cloud Build:
    ```bash
    gcloud builds submit --config cloudbuild.yaml
    ```

2. Access the deployed application via the provided URL.


## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contact

For any inquiries, please contact [Mustafa Keser](mailto:mustafakeser@zoho.com).

