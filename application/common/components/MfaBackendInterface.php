<?php

namespace common\components;

interface MfaBackendInterface
{
    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @param string $mfaExternalUuid (only used for WebAuthn)
     * @param string $rpOrigin Relying Party Origin (only used for WebAuthn)
     * @param string $recoveryEmail for mfa account recovery (only used for WebAuthn)
     * @return array
     */
    public function regInit(int $userId, string $mfaExternalUuid, string $rpOrigin, string $recoveryEmail): array;

    /**
     * Initialize authentication sequence
     * @param int $mfaId
     * @param string $rpOrigin Relying Party Origin (only used for WebAuthn)
     * @return array
     */
    public function authInit(int $mfaId, string $rpOrigin = ''): array;

    /**
     * Verify response from user is correct for the MFA backend device
     * @param int $mfaId The MFA ID
     * @param string $value Value provided by user
     * @param string $rpOrigin Relying Party Origin (only used for WebAuthn)
     * @param string $verifyType The type of verification: either "registration" or assumed to be for login (only used for WebAuthn)
     * @param string $label The label of a new webauthn (only used for WebAuthn)
     * @return bool|string
     */
    public function verify(int $mfaId, string $value, string $rpOrigin = '', string $verifyType = '', string $label = '');

    /**
     * Delete MFA backend configuration
     * @param int $mfaId
     * @param int $childId the id of the related/child object (only used for the WebAuthn backend)
     * @return bool
     */
    public function delete(int $mfaId, int $childId = 0): bool;

}
