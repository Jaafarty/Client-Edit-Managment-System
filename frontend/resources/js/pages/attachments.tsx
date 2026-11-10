import { Head, Link, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import type { FormEvent } from 'react';

type AttachmentsProps = {
    edit: { id: number; title: string };
    attachments: {
        id: number;
        original_name: string;
        description: string | null;
    }[];
    uploadUrl: string;
};

export default function Attachments({
    edit,
    attachments,
    uploadUrl,
}: AttachmentsProps) {
    const fileInput = useRef<HTMLInputElement>(null);
    const form = useForm<{ file: File | null; description: string }>({
        file: null,
        description: '',
    });

    function upload(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(uploadUrl, {
            onSuccess: () => {
                form.reset();
                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    }

    return (
        <main className="mx-auto max-w-2xl px-6 py-16">
            <Head title="Edit attachments" />
            <Link className="text-sm underline" href="/">
                Back to home
            </Link>
            <p className="mt-6 text-sm text-neutral-600">Edit #{edit.id}</p>
            <h1 className="text-2xl font-semibold">{edit.title}</h1>

            <form className="mt-6 space-y-4" onSubmit={upload}>
                <div>
                    <label className="block text-sm font-medium" htmlFor="file">
                        File
                    </label>
                    <input
                        className="mt-1 block w-full rounded-md border px-3 py-2"
                        id="file"
                        name="file"
                        type="file"
                        required
                        ref={fileInput}
                        onChange={(event) =>
                            form.setData(
                                'file',
                                event.target.files?.[0] ?? null,
                            )
                        }
                    />
                    <p className="mt-1 text-sm text-neutral-600">Up to 2 MB.</p>
                    {form.errors.file && (
                        <p className="mt-1 text-sm text-red-700" role="alert">
                            {form.errors.file}
                        </p>
                    )}
                </div>
                <div>
                    <label
                        className="block text-sm font-medium"
                        htmlFor="description"
                    >
                        Description (optional)
                    </label>
                    <textarea
                        className="mt-1 block w-full rounded-md border px-3 py-2"
                        id="description"
                        name="description"
                        maxLength={2000}
                        rows={3}
                        value={form.data.description}
                        onChange={(event) =>
                            form.setData('description', event.target.value)
                        }
                    />
                    {form.errors.description && (
                        <p className="mt-1 text-sm text-red-700" role="alert">
                            {form.errors.description}
                        </p>
                    )}
                </div>
                <button
                    className="rounded-md bg-neutral-900 px-4 py-2 text-white disabled:opacity-60"
                    disabled={form.processing}
                    type="submit"
                >
                    {form.processing ? 'Uploading…' : 'Upload attachment'}
                </button>
                {form.recentlySuccessful && (
                    <p role="status">Attachment uploaded.</p>
                )}
            </form>

            <section className="mt-8">
                <h2 className="text-xl font-semibold">Attachments</h2>
                {attachments.length > 0 ? (
                    <ul className="mt-3 divide-y">
                        {attachments.map((attachment) => (
                            <li className="py-3" key={attachment.id}>
                                <p className="font-medium wrap-break-word">
                                    {attachment.original_name}
                                </p>
                                {attachment.description && (
                                    <p className="text-sm break-words text-neutral-600">
                                        {attachment.description}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="mt-3 text-neutral-600">No attachments yet.</p>
                )}
            </section>
        </main>
    );
}
