# Use the official Python image as base image
FROM google/cloud-sdk:latest

# Set the working directory in the container
WORKDIR /app

# Install necessary packages
RUN apt-get update && apt-get install -y wget unzip nano libpq-dev bzip2 \
    build-essential pkg-config libmariadb-dev default-mysql-server

RUN service mariadb start

# Download and install Anaconda
RUN wget https://repo.anaconda.com/archive/Anaconda3-2022.05-Linux-x86_64.sh -O anaconda.sh
RUN bash anaconda.sh -b -p /opt/anaconda
RUN rm anaconda.sh

# Update PATH environment variable
ENV PATH /opt/anaconda/bin:$PATH

# Create conda environment with Python 3.10.14
RUN conda update -n base -c defaults conda
RUN conda create -n myenv python=3.10.14
RUN echo "source activate myenv" > ~/.bashrc
ENV PATH /opt/anaconda/envs/myenv/bin:$PATH

# Copy the current directory contents into the container at /app
COPY . /app

# Copy the requirements.txt file into the container
COPY ./requirements.txt /app/requirements.txt

# Install dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Install Terraform
RUN wget https://releases.hashicorp.com/terraform/1.8.3/terraform_1.8.3_linux_amd64.zip
RUN unzip terraform_1.8.3_linux_amd64.zip
RUN mv terraform /usr/local/bin/
RUN rm terraform_1.8.3_linux_amd64.zip

# Expose port 3000 to the outside world
EXPOSE 3000

# Command to run the Flask application
CMD ["gunicorn", "--bind", "0.0.0.0:3000", "app:app"]