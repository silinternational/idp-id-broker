<?php
namespace common\components;

use yii\base\Component;

class MfaBackendTotp extends Component implements MfaBackendInterface
{
    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @return array
     */
    public function regInit(int $userId): array
    {

    }

    /**
     * Initialize authentication sequence
     * @param int $mfaId
     * @return array
     */
    public function authInit(int $mfaId): array
    {

    }

    /**
     * Verify response from user is correct for the MFA backend device
     * @param int $mfaId The MFA ID
     * @param string $value Value provided by user, such as TOTP number or U2F challenge response
     * @return bool
     */
    public function verify(int $mfaId, string $value): bool
    {

    }
}