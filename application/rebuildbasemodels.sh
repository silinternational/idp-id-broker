#!/usr/bin/env bash

declare -A models
models["email"]="EmailBase"
models["email_log"]="EmailLogBase"
models["user"]="UserBase"
models["password"]="PasswordBase"
models["mfa"]="MfaBase"
models["mfa_backupcode"]="MfaBackupcodeBase"
models["mfa_webauthn"]="MfaWebauthnBase"
models["mfa_failed_attempt"]="MfaFailedAttemptBase"
models["method"]="MethodBase"
models["invite"]="InviteBase"

for i in "${!models[@]}"; do
    CMD="./yii gii/model --tableName=$i --modelClass=${models[$i]} --enableI18N=1 --overwrite=1 --interactive=0 --ns=\common\models"
    echo $CMD
    $CMD
done
