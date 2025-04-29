FROM hrishi2861/terabox:latest

WORKDIR /app

RUN apt update && apt install -y git build-essential

COPY requirements.txt .
RUN pip install --upgrade pip
RUN pip install --no-cache-dir -r requirements.txt

COPY . .
CMD ["bash", "start.sh"]
