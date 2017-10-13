<?php
namespace common\components;

interface MfaBackendInterface {

    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @return array
     */
    public function regInit(int $userId): array;

    /**
     * Initialize authentication sequence
     * @param int $mfaId
     * @return array
     */
    public function authInit(int $mfaId): array;

    /**
     * Verify response from user is correct for the MFA backend device
     * @param int $mfaId The MFA ID
     * @param string $value Value provided by user
     * @return bool
     */
    public function verify(int $mfaId, $value): bool;

    /**
     * Delete MFA backend configuration
     * @param int $mfaId
     * @return bool
     */
    public function delete(int $mfaId): bool;

}
