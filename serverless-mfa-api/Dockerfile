FROM golang:1.18

RUN curl -o- -L https://slss.io/install | VERSION=3.7.5 bash && \
  mv $HOME/.serverless/bin/serverless /usr/local/bin && \
  ln -s /usr/local/bin/serverless /usr/local/bin/sls

WORKDIR /src
RUN mkdir server
ENV BRANCH=main

RUN curl -sSfL https://raw.githubusercontent.com/cosmtrek/air/master/install.sh | sh -s -- -b $(go env GOPATH)/bin

RUN curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/apikey.go -o ./apikey.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/apikey_test.go -o ./apikey_test.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/config.go -o ./config.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/storage.go -o ./storage.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/user.go -o ./user.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/webauthn.go -o ./webauthn.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/webauthn_test.go -o ./webauthn_test.go \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/go.mod -o ./go.mod

RUN curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/server/main.go \
  -o ./server/main.go  \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/server/authentication_middleware.go \
  -o ./server/authentication_middleware.go  \
 && curl -sSfL https://raw.githubusercontent.com/silinternational/serverless-mfa-api-go/$BRANCH/server/cors_middleware.go \
  -o ./server/cors_middleware.go


RUN go get ./...  && go mod tidy && go test ./...

EXPOSE 8080

CMD ["go run ./server/..."]