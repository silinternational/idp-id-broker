package main

import (
	"fmt"
	"os"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/awserr"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/dynamodb"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbattribute"
)

const ApiKeyTable = "ApiKey"
const WebauthnTable = "WebAuthn"

var storage *Storage
var awsCfg *aws.Config

type ApiKey struct {
	Key          string `json:"value"`
	HashedSecret string `json:"hashedApiSecret"`
	Email        string `json:"email"`
	CreatedAt    int    `json:"createdAt"`
	ActivatedAt  int    `json:"activatedAt"`
}

type WebauthnEntry struct {
	// Shared fields between U2F and WebAuthn
	ID          string `json:"uuid"`
	ApiKeyValue string `json:"apiKey"`

	// U2F fields
	EncryptedAppId     string `json:"encryptedAppId,omitempty"`
	EncryptedKeyHandle string `json:"encryptedKeyHandle,omitempty"`
	EncryptedPublicKey string `json:"encryptedPublicKey,omitempty"`

	// WebAuthn fields
	EncryptedSessionData []byte `json:"EncryptedSessionData,omitempty"`

	// These can be multiple Yubikeys or other WebAuthn entries
	EncryptedCredentials []byte `json:"EncryptedCredentials,omitempty"`
}

func initConfig() {
	if awsCfg != nil {
		return
	}

	awsCfg = &aws.Config{
		Endpoint:   aws.String(os.Getenv("AWS_ENDPOINT")),
		Region:     aws.String(os.Getenv("AWS_DEFAULT_REGION")),
		DisableSSL: aws.Bool(true),
	}
}

// Storage provides wrapper methods for interacting with DynamoDB
type Storage struct {
	awsSession *session.Session
	client     *dynamodb.DynamoDB
}

// Store puts item at key.
func (s *Storage) Store(table string, item interface{}) error {
	av, err := dynamodbattribute.MarshalMap(item)
	if err != nil {
		return err
	}

	input := &dynamodb.PutItemInput{
		Item:      av,
		TableName: aws.String(table),
	}

	_, err = s.client.PutItem(input)
	return err
}

func NewStorage() (*Storage, error) {
	initConfig()
	s := Storage{}

	var err error
	s.awsSession, err = session.NewSession(awsCfg)
	if err != nil {
		return &Storage{}, err
	}

	s.client = dynamodb.New(s.awsSession)
	if s.client == nil {
		return nil, fmt.Errorf("failed to create new dynamo client")
	}

	return &s, nil
}

func initDb() error {

	// attempt to delete tables in case already exists
	tables := map[string]string{WebauthnTable: "uuid", ApiKeyTable: "value"}
	for name, _ := range tables {
		deleteTable := &dynamodb.DeleteTableInput{
			TableName: aws.String(name),
		}
		_, err := storage.client.DeleteTable(deleteTable)
		if err != nil {
			if aerr, ok := err.(awserr.Error); ok {
				switch aerr.Code() {
				case dynamodb.ErrCodeResourceNotFoundException:
					// this is fine
				default:
					return aerr
				}
			} else {
				return err
			}
		}
	}

	// create tables
	for table, attr := range tables {
		createTable := &dynamodb.CreateTableInput{
			AttributeDefinitions: []*dynamodb.AttributeDefinition{
				{
					AttributeName: aws.String(attr),
					AttributeType: aws.String("S"),
				},
			},
			KeySchema: []*dynamodb.KeySchemaElement{
				{
					AttributeName: aws.String(attr),
					KeyType:       aws.String("HASH"),
				},
			},
			ProvisionedThroughput: &dynamodb.ProvisionedThroughput{
				ReadCapacityUnits:  aws.Int64(3),
				WriteCapacityUnits: aws.Int64(3),
			},
			TableName: aws.String(table),
		}
		_, err := storage.client.CreateTable(createTable)
		if err != nil {
			return err
		}
	}
	return nil
}

func initApiKeys() {

	apiKey := ApiKey{
		Key:          "EC7C2E16-5028-432F-8AF2-A79A64CF3BC1",
		HashedSecret: "$2y$10$HtvmT/nnfofEhoFNmtk/9OfP4DDJvjzSa5dVhtOKolwb8hc6gJ9LK",
		Email:        "example-user@example.com",
		ActivatedAt:  1590518082000,
		CreatedAt:    1590518082000,
	}
	if err := storage.Store(ApiKeyTable, apiKey); err != nil {
		panic("error storing new api key: " + err.Error())
	}
}

func initWebauthnEntries() {

	w := WebauthnEntry{
		ID:                   "097791bf-2385-4ab4-8b06-14561a338d8e",
		ApiKeyValue:          "EC7C2E16-5028-432F-8AF2-A79A64CF3BC1",
		EncryptedAppId:       "SomeEncryptedAppId",
		EncryptedKeyHandle:   "SomeEncryptedKeyHandle",
		EncryptedCredentials: []byte{},
	}
	if err := storage.Store(WebauthnTable, w); err != nil {
		panic("error storing new webauthn entry: " + err.Error())
	}
}

func main() {

	awsCfg = &aws.Config{
		Endpoint:   aws.String(os.Getenv("AWS_ENDPOINT")),
		Region:     aws.String(os.Getenv("AWS_DEFAULT_REGION")),
		DisableSSL: aws.Bool(true),
	}

	newSt, err := NewStorage()
	if err != nil {
		panic("error initializing storage: " + err.Error())
	}
	storage = newSt

	if err := initDb(); err != nil {
		panic("error initializing dynamo db: " + err.Error())
	}

	initApiKeys()
	initWebauthnEntries()

}
