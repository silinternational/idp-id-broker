<?php
namespace common\components;

interface MfaBackendInterface
{

    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @param string $rpOrigin Relying Party Origin (only used for WebAuthn)
     * @return array
     */
    public function regInit(int $userId, string $rpOrigin): array;

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
     * @return bool|string
     */
    public function verify(int $mfaId, string $value, string $rpOrigin = '');

    /**
     * Delete MFA backend configuration
     * @param int $mfaId
     * @param int $childId the id of the related/child object (only used for the WebAuthn backend)
     * @return bool
     */
    public function delete(int $mfaId, int $childId = 0): bool;

}
