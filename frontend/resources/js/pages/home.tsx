import { Link } from '@inertiajs/react';

type HomeProps = {
    name: string;
    edits: { id: number; title: string }[];
};

export default function Home({ name, edits }: HomeProps) {
    return (
        <main className="mx-auto max-w-4xl px-6 py-16">
            <header className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-3xl font-semibold tracking-tight">
                    {name}
                </h1>
                <Link
                    as="button"
                    className="rounded-md border px-4 py-2"
                    href="/logout"
                    method="post"
                    replace
                >
                    Sign out
                </Link>
            </header>
            <Link className="mt-6 inline-block underline" href="/edits">
                Manage edits
            </Link>
            <section className="mt-8">
                <h2 className="text-xl font-semibold">Recent edits</h2>
                {edits.length > 0 ? (
                    <ul className="mt-3 space-y-2">
                        {edits.map((edit) => (
                            <li key={edit.id}>
                                <Link
                                    className="underline"
                                    href={'/edits/' + edit.id + '/attachments'}
                                >
                                    {edit.title} attachments
                                </Link>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="mt-3 text-neutral-600">
                        Add an edit before uploading attachments.
                    </p>
                )}
            </section>
        </main>
    );
}
