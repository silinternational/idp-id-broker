FROM golang:1.18

RUN curl -o- -L https://slss.io/install | VERSION=3.7.5 bash && \
  mv $HOME/.serverless/bin/serverless /usr/local/bin && \
  ln -s /usr/local/bin/serverless /usr/local/bin/sls


WORKDIR /initDynamo
COPY ./initDynamo /initDynamo/
RUN go get ./... && go mod tidy && go build ./...

WORKDIR /src

RUN mkdir override
RUN mkdir server
ENV BRANCH=develop

RUN curl -sSfL https://raw.githubusercontent.com/cosmtrek/air/master/install.sh | sh -s -- -b $(go env GOPATH)/bin

RUN curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/apikey.go -o ./apikey.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/config.go -o ./config.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/storage.go -o ./storage.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/user.go -o ./user.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/webauthn.go -o ./webauthn.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/go.mod -o ./go.mod

RUN curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/server/main.go \
  -o ./server/main.go  \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/server/authentication_middleware.go \
  -o ./server/authentication_middleware.go  \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/server/cors_middleware.go \
  -o ./server/cors_middleware.go

ENV AWS_ENDPOINT=dynamo:8000
ENV AWS_DEFAULT_REGION=us-east-1
ENV AWS_ACCESS_KEY_ID=abc123
ENV AWS_SECRET_ACCESS_KEY=abc123
ENV AWS_DISABLE_SSL="true"
RUN go get ./...  && go mod tidy && go build ./...

EXPOSE 8080

CMD ["go run ./server/..."]