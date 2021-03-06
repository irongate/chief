<?php

namespace IronGate\Chief\Http\Controllers\API;

use Illuminate\Http\Response;
use GraphQL\Utils\SchemaPrinter;
use Nuwave\Lighthouse\GraphQL as Lighthouse;
use Nuwave\Lighthouse\Execution\GraphQLRequest;
use GraphQL\Validator\Rules\DisableIntrospection;
use Nuwave\Lighthouse\Support\Http\Controllers\GraphQLController;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GraphQL extends GraphQLController
{
    /**
     * {@inheritdoc}
     */
    public function query(GraphQLRequest $request, string $guard = 'api'): SymfonyResponse
    {
        auth()->shouldUse($guard);

        sync_user_timezone();

        config([
            'lighthouse.security.disable_introspection' => auth()->check()
                ? DisableIntrospection::DISABLED
                : DisableIntrospection::ENABLED,
        ]);

        // If we are in a local environment we print the schema on every request
        // it's a bit wasteful but the impact is not that big and it saves using git hooks
        if (app()->environment('local')) {
            file_put_contents(
                base_path('routes/graphql/exported/schema.public.graphql'),
                SchemaPrinter::doPrint($this->graphQL->prepSchema())
            );

            config(['lighthouse.security.disable_introspection' => DisableIntrospection::DISABLED]);
        }

        return parent::query($request);
    }

    /**
     * Execute GraphQL query from a session authenticated context.
     *
     * @param \Nuwave\Lighthouse\Execution\GraphQLRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function queryWeb(GraphQLRequest $request): Response
    {
        return $this->query($request, 'web');
    }

    /**
     * Respond with the full GraphQL schema file.
     *
     * @param \Nuwave\Lighthouse\GraphQL $graphQL
     *
     * @return \Illuminate\Http\Response
     */
    public function schema(Lighthouse $graphQL): Response
    {
        $schema = SchemaPrinter::doPrint($graphQL->prepSchema());

        $appId = config('chief.id', 'app');

        return response()->make($schema, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => "inline; filename=\"{$appId}-schema.graphql\"",
        ]);
    }
}
