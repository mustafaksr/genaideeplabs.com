# Use the official lightweight Python image
FROM us-docker.pkg.dev/vertex-ai/training/sklearn-cpu.1-0:latest

# Set the working directory
WORKDIR /app

# Copy the requirements file into the container
COPY requirements.txt .

RUN apt-get update && apt-get install -y libgomp1
# Install the dependencies
RUN pip3 install --no-cache-dir -r requirements.txt

# Copy the current directory contents into the container
COPY . .

# Define the command to run the script with arguments passed
ENTRYPOINT ["python3", "train.py"]
