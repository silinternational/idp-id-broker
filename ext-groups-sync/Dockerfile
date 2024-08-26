FROM ubuntu:24.04

RUN apt-get update && apt-get install -y \
      curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

RUN mkdir -p ~/.aws-lambda-rie
RUN curl -Lo ~/.aws-lambda-rie/aws-lambda-rie https://github.com/aws/aws-lambda-runtime-interface-emulator/releases/latest/download/aws-lambda-rie
RUN chmod +x ~/.aws-lambda-rie/aws-lambda-rie

RUN curl --location -o personnel-sync.tar.gz https://github.com/silinternational/personnel-sync/releases/download/v6.8.3/personnel-sync_6.8.3_linux_amd64.tar.gz
RUN tar -xzf personnel-sync.tar.gz
RUN chmod +x personnel-sync

CMD ["~/.aws-lambda-rie/aws-lambda-rie", "./personnel-sync"]
