<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class ValidateSignature
{
    /**
     * The names of the query string parameters that should be ignored.
     *
     * @var array<int, string>
     */
    protected $except = [
        // 'fbclid',
        // 'utm_campaign',
        // 'utm_content',
        // 'utm_medium',
        // 'utm_source',
        // 'utm_term',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, \Closure $next, ?string $relative = null)
    {
        $ignore = property_exists($this, 'except') ? $this->except : [];

        $request = $this->handlePendingSignature($request, $ignore);

        if ($this->hasValidSignature($request, $relative !== 'relative') ||
            $request->attributes->get('validated_using_relative_signature', false)) {
            return $next($request);
        }

        return App::abort(403, 'Invalid signature.');
    }

    /**
     * Determine if the given request has a valid signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $absolute
     * @return bool
     */
    public function hasValidSignature($request, $absolute = true)
    {
        return $this->hasCorrectSignature($request, $absolute) &&
            $this->signatureHasNotExpired($request);
    }

    /**
     * Determine if the signature from the given request matches the URL's signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $absolute
     * @return bool
     */
    public function hasCorrectSignature($request, $absolute = true)
    {
        $url = $absolute ? $request->url() : '/'.$request->path();

        $queryParams = collect($request->query())
            ->except('signature')
            ->all();

        ksort($queryParams);

        $signature = hash_hmac('sha256', $url.'?'.http_build_query($queryParams), config('app.key'));

        return hash_equals($signature, (string) $request->query('signature', ''));
    }

    /**
     * Determine if the expires timestamp from the given request is not from the past.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function signatureHasNotExpired($request)
    {
        $expires = $request->query('expires');

        return ! ($expires && now()->getTimestamp() > $expires);
    }

    /**
     * Handle the pending signature in the request, if it exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array<int, string>  $queryParams
     * @return \Illuminate\Http\Request
     */
    protected function handlePendingSignature($request, array $ignoreParams)
    {
        if ($request->query('signature_pending') !== null) {
            $signature = $request->query('signature');

            $url = $request->url();

            $queryParams = collect($request->query())
                ->except(['signature', 'signature_pending'])
                ->filter(function ($value, $key) use ($ignoreParams) {
                    return ! in_array($key, $ignoreParams);
                })->all();

            ksort($queryParams);

            $relativePath = '/'.$request->path();

            $relativeSignature = hash_hmac('sha256', $relativePath.'?'.http_build_query($queryParams), config('app.key'));

            $hasValidRelativeSignature = hash_equals($relativeSignature, (string) $signature);

            $absolute = Str::endsWith($signature, '-absolute');

            $hasValidAbsoluteSignature = false;

            if ($absolute) {
                $absoluteSignature = hash_hmac(
                    'sha256', $url.'?'.http_build_query($queryParams), config('app.key')
                ).'-absolute';

                $hasValidAbsoluteSignature = hash_equals($absoluteSignature, (string) $signature);
            }

            if ($hasValidAbsoluteSignature || $hasValidRelativeSignature) {
                $request->attributes->set('validated_using_relative_signature', $hasValidRelativeSignature && ! $hasValidAbsoluteSignature);

                return $request->duplicate(null, null, null, collect($request->query())
                    ->except(['signature', 'signature_pending'])
                    ->all());
            }
        }

        return $request;
    }
}
