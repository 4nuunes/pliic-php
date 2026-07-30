<?php

declare(strict_types=1);

namespace Pliic\Exceptions;

/**
 * The key is valid, but it does not carry the scope the endpoint requires.
 *
 * Newly created Pliic keys are read-only, so this is the first thing most
 * integrations hit on their first write. It extends `PermissionException`
 * on purpose: existing `catch (PermissionException)` blocks keep working,
 * and code that wants to tell "wrong key permissions" apart from any other
 * 403 catches this instead.
 *
 * ```php
 * try {
 *     $pliic->tickets->create([...]);
 * } catch (InsufficientScopeException $e) {
 *     // $e->requiredScope()  => 'tickets:write'
 *     // $e->grantedScopes()  => ['suggestions:read', 'tickets:read']
 *     // $e->manageScopesUrl() => https://pliic.com/team/acme/settings/api-keys
 * }
 * ```
 */
class InsufficientScopeException extends PermissionException
{
    /** Stable code the API sends in the `error` field of the 403 body. */
    public const API_ERROR_CODE = 'insufficient_scope';

    /**
     * Builds the exception with a message that is actionable on its own, no
     * matter how terse the server was — older API builds answered a bare
     * "Insufficient scope." The wording is derived from the structured
     * fields, so nothing here depends on parsing prose; the server's own
     * message stays reachable through `$e->body['message']`.
     *
     * @param  array<string, mixed>|null  $body
     */
    public static function fromApiError(string $apiMessage, int $status, ?array $body): self
    {
        $exception = new self($apiMessage, $status, $body);

        $requiredScope = $exception->requiredScope();

        if ($requiredScope === null || $requiredScope === '') {
            return $exception;
        }

        return new self($exception->buildMessage($requiredScope), $status, $body);
    }

    /**
     * The scope the endpoint demanded, e.g. `tickets:write`. Null only when
     * the API omitted it from the body.
     */
    public function requiredScope(): ?string
    {
        $scope = $this->body['required_scope'] ?? null;

        return is_string($scope) ? $scope : null;
    }

    /**
     * The scopes the key actually carries — compare against
     * {@see self::requiredScope()} to see what is missing.
     *
     * @return array<int, string>
     */
    public function grantedScopes(): array
    {
        $scopes = $this->body['granted_scopes'] ?? [];

        if (! is_array($scopes)) {
            return [];
        }

        return array_values(array_filter($scopes, 'is_string'));
    }

    /**
     * Direct link to the screen that grants scopes for this app, when the
     * API could resolve it.
     */
    public function manageScopesUrl(): ?string
    {
        $url = $this->body['manage_scopes_url'] ?? null;

        return is_string($url) ? $url : null;
    }

    public function docsUrl(): ?string
    {
        $url = $this->body['docs_url'] ?? null;

        return is_string($url) ? $url : null;
    }

    private function buildMessage(string $requiredScope): string
    {
        $granted = $this->grantedScopes();

        $message = sprintf(
            'This Pliic API key is not allowed to perform this request: it is missing the "%s" scope. '
            .'This is a permission on the key, not a problem with the data you sent. '
            .'The key currently has: %s.',
            $requiredScope,
            $granted === [] ? 'no scopes' : '"'.implode('", "', $granted).'"',
        );

        $manageScopesUrl = $this->manageScopesUrl();

        if ($manageScopesUrl !== null) {
            return $message.' Enable the missing scope under Settings → API Keys → Scopes: '.$manageScopesUrl;
        }

        return $message.' Enable the missing scope in Pliic under Settings → API Keys → Scopes.';
    }
}
