# Use the official Python image as base image
FROM python:3.9-slim

# Set the working directory in the container
WORKDIR /app

# Install necessary packages
RUN apt-get update && apt-get install -y wget unzip nano libpq-dev bzip2 \
    build-essential pkg-config libmariadb-dev default-mysql-server \
    && rm -rf /var/lib/apt/lists/*

RUN service mariadb start

# Copy the current directory contents into the container at /app
COPY . /app

# Copy the requirements.txt file into the container
COPY ./requirements.txt /app/requirements.txt

# Install dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Expose port 8001 to the outside world
EXPOSE 8001

# Command to run the Flask application
CMD ["gunicorn", "--bind", "0.0.0.0:8001", "app:app"]
