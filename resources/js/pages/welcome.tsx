import { Head } from '@inertiajs/react';

export default function Welcome() {
    const apiBase =
        typeof window !== 'undefined'
            ? `${window.location.origin}/api`
            : '/api';

    return (
        <>
            <Head title="Feedback Analysis API">
                <meta
                    name="description"
                    content="Customer feedback analysis API powered by Anthropic Claude. Submit feedback in any language; get an English summary, sentiment, and detected language."
                />
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700"
                    rel="stylesheet"
                />
            </Head>
            <div
                className="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
                data-page="feedback-analysis-welcome"
            >
                <header className="border-b border-slate-200/80 bg-white/80 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 sm:px-6">
                        <span className="text-lg font-semibold tracking-tight text-slate-800 dark:text-slate-200">
                            Feedback Analysis API
                        </span>
                        <nav className="flex items-center gap-4 text-sm">
                            <a
                                href={`${apiBase}/health`}
                                className="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Health
                            </a>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto max-w-4xl px-4 py-12 sm:px-6 sm:py-16">
                    <section className="mb-16 text-center">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                            Analyse customer feedback with AI
                        </h1>
                        <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-600 dark:text-slate-400">
                            Send feedback in any language. Get an English
                            summary, sentiment, and detected language—powered by
                            Anthropic Claude.
                        </p>
                    </section>

                    <section className="mb-16">
                        <h2 className="mb-6 text-xl font-semibold text-slate-800 dark:text-slate-200">
                            What it does
                        </h2>
                        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {[
                                {
                                    title: 'Any language',
                                    description:
                                        'Submit feedback in English, Indonesian, Japanese, or any language. Detection is automatic.',
                                },
                                {
                                    title: 'English output',
                                    description:
                                        'Summary and sentiment are always returned in English for consistent reporting.',
                                },
                                {
                                    title: 'Sentiment & summary',
                                    description:
                                        'Get positive, neutral, or negative sentiment plus a short, readable summary.',
                                },
                            ].map((item) => (
                                <li
                                    key={item.title}
                                    className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
                                >
                                    <h3 className="font-medium text-slate-900 dark:text-slate-100">
                                        {item.title}
                                    </h3>
                                    <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                        {item.description}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section className="mb-16">
                        <h2 className="mb-6 text-xl font-semibold text-slate-800 dark:text-slate-200">
                            API at a glance
                        </h2>
                        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                            <div className="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                                <code className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    POST {apiBase}/analyse-feedback
                                </code>
                            </div>
                            <div className="space-y-4 p-4 text-sm">
                                <div>
                                    <p className="mb-1 font-medium text-slate-600 dark:text-slate-400">
                                        Request body
                                    </p>
                                    <pre className="overflow-x-auto rounded-lg bg-slate-100 p-4 font-mono text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                                        {`{
  "feedback_text": "Your customer feedback here (required, non-empty)"
}`}
                                    </pre>
                                </div>
                                <div>
                                    <p className="mb-1 font-medium text-slate-600 dark:text-slate-400">
                                        Success response (200)
                                    </p>
                                    <pre className="overflow-x-auto rounded-lg bg-slate-100 p-4 font-mono text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                                        {`{
  "summary": "Short English summary of the feedback.",
  "sentiment": "positive",
  "language": "english"
}`}
                                    </pre>
                                </div>
                                <p className="text-slate-600 dark:text-slate-400">
                                    Errors: 400 (missing/invalid body), 429
                                    (rate limit), 502 (AI error), 503 (API key
                                    missing), 500 (server error).
                                </p>
                            </div>
                        </div>
                    </section>

                    <section className="mb-16">
                        <h2 className="mb-6 text-xl font-semibold text-slate-800 dark:text-slate-200">
                            Tech stack
                        </h2>
                        <div className="flex flex-wrap gap-3">
                            {[
                                'Laravel 12',
                                'React 19',
                                'Inertia.js',
                                'Vite',
                                'Tailwind CSS',
                                'Anthropic Claude',
                            ].map((tech) => (
                                <span
                                    key={tech}
                                    className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    {tech}
                                </span>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-xl border border-slate-200 bg-slate-100/50 p-6 sm:p-8 dark:border-slate-700 dark:bg-slate-800/30">
                        <h2 className="mb-4 text-xl font-semibold text-slate-800 dark:text-slate-200">
                            Get started
                        </h2>
                        <p className="mb-4 text-slate-600 dark:text-slate-400">
                            Configure{' '}
                            <code className="rounded bg-slate-200 px-1.5 py-0.5 font-mono text-sm dark:bg-slate-700">
                                ANTHROPIC_API_KEY
                            </code>{' '}
                            in your environment, then call the API with{' '}
                            <code className="rounded bg-slate-200 px-1.5 py-0.5 font-mono text-sm dark:bg-slate-700">
                                Content-Type: application/json
                            </code>
                            . Rate limiting applies per minute (configurable).
                            See the README in the project root for setup,
                            deployment on AWS EC2, and full API reference.
                        </p>
                        <div className="flex flex-wrap items-center gap-4">
                            <a
                                href={`${apiBase}/health`}
                                className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-slate-900"
                            >
                                Check API health
                            </a>
                        </div>
                    </section>

                    <footer className="mt-16 border-t border-slate-200 pt-8 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        <p>
                            Feedback Analysis API — Laravel + Inertia +
                            Anthropic Claude
                        </p>
                    </footer>
                </main>
            </div>
        </>
    );
}
