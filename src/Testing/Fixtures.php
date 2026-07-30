<?php

declare(strict_types=1);

namespace Pliic\Testing;

/**
 * Realistic example payloads matching the shapes documented in Pliic's
 * OpenAPI spec (`app/Support/OpenApiSpec.php` in the main Pliic app).
 *
 * Kept honest by `tests/Feature/Api/ApiSdkFixturesMatchOpenApiTest.php` in
 * the monorepo: that test derives the expected shape straight from the spec
 * and fails the build if these fixtures drift from it.
 */
final class Fixtures
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function suggestion(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'title' => 'Dark mode',
            'body' => 'It would be easier on the eyes at night.',
            'status' => 'pending',
            'vote_count' => 3,
            'comments_count' => 1,
            'is_pinned' => false,
            'author' => self::suggestionAuthor(),
            'created_at' => '2026-01-15T10:00:00.000000Z',
            'updated_at' => '2026-01-15T10:00:00.000000Z',
            'user_has_voted' => false,
            'is_author' => false,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function suggestionAuthor(array $overrides = []): array
    {
        return array_merge([
            'external_id' => 'u_123',
            'name' => 'Ana',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{data: array<string, mixed>}
     */
    public static function suggestionResponse(array $overrides = []): array
    {
        return ['data' => self::suggestion($overrides)];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public static function suggestionsListResponse(array $items = []): array
    {
        return self::paginate($items === [] ? [self::suggestion()] : $items);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function commentAuthor(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana',
            'avatar_url' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function suggestionComment(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'body' => 'Same here, would love this.',
            'author_type' => 'app_user',
            'author' => self::commentAuthor(),
            'created_at' => '2026-01-15T10:05:00.000000Z',
            'replies' => [],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{data: array<string, mixed>}
     */
    public static function suggestionCommentResponse(array $overrides = []): array
    {
        return ['data' => self::suggestionComment($overrides)];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{data: list<array<string, mixed>>}
     */
    public static function suggestionCommentsResponse(array $items = []): array
    {
        return ['data' => $items === [] ? [self::suggestionComment()] : $items];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function voteResult(array $overrides = []): array
    {
        return array_merge([
            'action' => 'voted',
            'votes_count' => 4,
            'user_has_voted' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{data: array<string, mixed>}
     */
    public static function voteResultResponse(array $overrides = []): array
    {
        return ['data' => self::voteResult($overrides)];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function ticketMessage(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'sender_type' => 'user',
            'body' => 'Any update on this?',
            'author' => self::commentAuthor(),
            'created_at' => '2026-01-15T10:10:00.000000Z',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{data: array<string, mixed>}
     */
    public static function ticketMessageResponse(array $overrides = []): array
    {
        return ['data' => self::ticketMessage($overrides)];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function ticketAuthor(array $overrides = []): array
    {
        return array_merge([
            'external_id' => 'u_123',
            'name' => 'Ana',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<array<string, mixed>>|null  $messages  Omit (null) for the list shape, pass items (or [] for a sample) for the detail shape.
     * @return array<string, mixed>
     */
    public static function ticket(array $overrides = [], ?array $messages = null): array
    {
        $ticket = array_merge([
            'id' => 1,
            'ticket_number' => 'PLI-1001',
            'subject' => 'Export button does nothing',
            'type' => 'bug',
            'priority' => 'normal',
            'status' => 'open',
            'tags' => [],
            'author' => self::ticketAuthor(),
            'created_at' => '2026-01-15T09:00:00.000000Z',
            'updated_at' => '2026-01-15T09:00:00.000000Z',
            'resolved_at' => null,
        ], $overrides);

        if ($messages !== null) {
            $ticket['messages'] = $messages === [] ? [self::ticketMessage()] : $messages;
        }

        return $ticket;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<array<string, mixed>>|null  $messages  Omit (null) for the list shape, pass items (or [] for a sample) for the detail shape.
     * @return array{data: array<string, mixed>}
     */
    public static function ticketResponse(array $overrides = [], ?array $messages = null): array
    {
        return ['data' => self::ticket($overrides, $messages)];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{data: array<string, mixed>}
     */
    public static function ticketDetailResponse(array $overrides = []): array
    {
        return self::ticketResponse($overrides, messages: []);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public static function ticketsListResponse(array $items = []): array
    {
        return self::paginate($items === [] ? [self::ticket()] : $items);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function survey(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'app_id' => 1,
            'title' => 'NPS Q1',
            'status' => 'active',
            'submitted_count' => 42,
            'dismissed_count' => 8,
            'created_at' => '2026-01-01T00:00:00.000000Z',
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public static function surveysListResponse(array $items = []): array
    {
        return self::paginate($items === [] ? [self::survey()] : $items);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function surveyResults(array $overrides = []): array
    {
        return array_merge([
            'survey_id' => 1,
            'title' => 'NPS Q1',
            'submitted' => 42,
            'dismissed' => 8,
            'completion_rate' => 84.0,
            'questions' => [],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{data: array<string, mixed>}
     */
    public static function surveyResultsResponse(array $overrides = []): array
    {
        return ['data' => self::surveyResults($overrides)];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function analytics(array $overrides = []): array
    {
        return array_replace_recursive([
            'suggestions' => ['total' => 12, 'by_status' => ['pending' => 5, 'planned' => 3, 'done' => 4], 'last_30_days' => 6],
            'tickets' => ['total' => 8, 'by_status' => ['open' => 3, 'resolved' => 5], 'last_30_days' => 2],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{data: array<string, mixed>}
     */
    public static function analyticsResponse(array $overrides = []): array
    {
        return ['data' => self::analytics($overrides)];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function appUser(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'external_id' => 'u_123',
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'avatar_url' => null,
            'metadata' => null,
            'is_blocked' => false,
            'last_seen_at' => '2026-01-15T08:00:00.000000Z',
            'email_notifications_disabled_at' => null,
            'created_at' => '2026-01-01T00:00:00.000000Z',
            'suggestions' => [self::suggestion()],
            'tickets' => [self::ticket()],
            'votes' => 2,
            'comments' => 1,
            'votes_detail' => [
                ['suggestion_id' => 1, 'created_at' => '2026-01-15T10:00:00.000000Z'],
            ],
            'comments_detail' => [
                ['id' => 1, 'suggestion_id' => 1, 'body' => 'Same here.', 'created_at' => '2026-01-15T10:05:00.000000Z'],
            ],
            'survey_responses' => [
                [
                    'id' => 1,
                    'survey_title' => 'NPS Q1',
                    'status' => 'completed',
                    'completed_at' => '2026-01-10T00:00:00.000000Z',
                    'answers' => [
                        ['question' => 'How likely are you to recommend us?', 'value' => '9'],
                    ],
                ],
            ],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{data: array<string, mixed>}
     */
    public static function appUserResponse(array $overrides = []): array
    {
        return ['data' => self::appUser($overrides)];
    }

    /**
     * @return array{message: string}
     */
    public static function erasureResponse(string $message = 'User data erased.'): array
    {
        return ['message' => $message];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    public static function error(string $message, array $errors = []): array
    {
        return $errors === [] ? ['message' => $message] : ['message' => $message, 'errors' => $errors];
    }

    /**
     * The 403 body the API returns when the key lacks a scope, so an
     * integration can rehearse the path its first write really takes.
     *
     * @param  list<string>  $grantedScopes
     * @return array<string, mixed>
     */
    public static function insufficientScopeError(
        string $requiredScope = 'suggestions:write',
        array $grantedScopes = ['suggestions:read', 'tickets:read'],
    ): array {
        return [
            'message' => sprintf(
                'This API key is not allowed to perform this request: it is missing the "%s" scope. '
                .'This is a permission on the key itself, not a problem with the data you sent. '
                .'Enable the scope in Pliic under Settings → API Keys → Scopes for this app, then retry.',
                $requiredScope,
            ),
            'error' => 'insufficient_scope',
            'required_scope' => $requiredScope,
            'granted_scopes' => $grantedScopes,
            'manage_scopes_url' => 'https://pliic.com/team/acme/settings/api-keys',
            'docs_url' => 'https://docs.pliic.com/integrations/api-keys/',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{data: list<array<string, mixed>>, links: array<string, mixed>, meta: array<string, mixed>}
     */
    public static function paginate(array $items, int $currentPage = 1, int $perPage = 15, ?int $total = null): array
    {
        $total ??= count($items);

        return [
            'data' => array_values($items),
            'links' => [
                'first' => '/?page=1',
                'last' => '/?page=1',
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'current_page' => $currentPage,
                'from' => $items === [] ? null : 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'to' => $items === [] ? null : count($items),
                'total' => $total,
            ],
        ];
    }
}
