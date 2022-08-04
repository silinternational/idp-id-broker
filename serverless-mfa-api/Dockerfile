FROM silintl/serverless-mfa-api-go:develop
WORKDIR /src

RUN mkdir override

ENV AWS_ENDPOINT=dynamo:8000
ENV AWS_DEFAULT_REGION=us-east-1
ENV AWS_ACCESS_KEY_ID=abc123
ENV AWS_SECRET_ACCESS_KEY=abc123
ENV AWS_DISABLE_SSL="true"
RUN go get ./...  && go mod tidy && go build ./...

EXPOSE 8080

CMD ["go run ./server/..."]