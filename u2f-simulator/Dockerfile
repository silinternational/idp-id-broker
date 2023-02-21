FROM silintl/serverless-mfa-api-go:develop
WORKDIR /src

RUN mkdir override

RUN go get ./...  && go mod tidy && go build ./...

EXPOSE 8080

CMD ["go run ./u2fserver/..."]