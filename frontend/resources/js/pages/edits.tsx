import { Head, Link, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

type ProjectOption = { id: number; name: string };

type EditRecord = {
    id: number;
    project_id: number;
    project: ProjectOption;
    title: string;
    description: string | null;
    priority: string;
    status: string;
    price: string | null;
    due_date: string | null;
    is_paid: boolean;
};

type EditOptions = {
    projects: ProjectOption[];
    priorities: string[];
    statuses: string[];
};

type EditResponse = { data: EditRecord };

const apiUrl = '/api/edits';
const fieldClass = 'mt-1 block w-full rounded-md border px-3 py-2';

function EditForm({
    edit,
    projects,
    priorities,
    statuses,
    onSaved,
    onCancel,
}: EditOptions & {
    edit: EditRecord | null;
    onSaved: (edit: EditRecord) => void;
    onCancel: () => void;
}) {
    const form = useHttp<
        {
            project_id: string;
            title: string;
            description: string;
            priority: string;
            status: string;
            price: string;
            due_date: string;
        },
        EditResponse
    >({
        project_id: edit ? String(edit.project_id) : '',
        title: edit?.title ?? '',
        description: edit?.description ?? '',
        priority: edit?.priority ?? 'Medium',
        status: edit?.status ?? 'Pending',
        price: edit?.price ?? '',
        due_date: edit?.due_date ?? '',
    });
    const [requestError, setRequestError] = useState('');

    async function save(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setRequestError('');
        const options = {
            onSuccess: ({ data }: EditResponse) => onSaved(data),
        };

        try {
            if (edit) {
                await form.patch(`${apiUrl}/${edit.id}`, options);
            } else {
                await form.post(apiUrl, options);
            }
        } catch {
            setRequestError('Could not save the edit. Please try again.');
        }
    }

    return (
        <form className="mt-6 space-y-4 rounded-md border p-4" onSubmit={save}>
            <h2 className="text-lg font-semibold">
                {edit ? 'Edit details' : 'New edit'}
            </h2>
            <fieldset
                className="grid gap-4 sm:grid-cols-2"
                disabled={form.processing}
            >
                <label>
                    Title
                    <input
                        className={fieldClass}
                        required
                        maxLength={255}
                        value={form.data.title}
                        onChange={(event) =>
                            form.setData('title', event.target.value)
                        }
                    />
                </label>
                <label>
                    Project
                    <select
                        className={fieldClass}
                        required
                        value={form.data.project_id}
                        onChange={(event) =>
                            form.setData('project_id', event.target.value)
                        }
                    >
                        <option value="">Select a project</option>
                        {projects.map((project) => (
                            <option key={project.id} value={project.id}>
                                {project.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    Priority
                    <select
                        className={fieldClass}
                        value={form.data.priority}
                        onChange={(event) =>
                            form.setData('priority', event.target.value)
                        }
                    >
                        {priorities.map((priority) => (
                            <option key={priority}>{priority}</option>
                        ))}
                    </select>
                </label>
                <label>
                    Status
                    <select
                        className={fieldClass}
                        value={form.data.status}
                        onChange={(event) =>
                            form.setData('status', event.target.value)
                        }
                    >
                        {statuses.map((status) => (
                            <option key={status}>{status}</option>
                        ))}
                    </select>
                </label>
                <label>
                    Price (optional)
                    <input
                        className={fieldClass}
                        type="number"
                        step="0.01"
                        min="-99999999.99"
                        max="99999999.99"
                        value={form.data.price}
                        onChange={(event) =>
                            form.setData('price', event.target.value)
                        }
                    />
                </label>
                <label>
                    Due date (optional)
                    <input
                        className={fieldClass}
                        type="date"
                        min="1000-01-01"
                        max="9999-12-31"
                        value={form.data.due_date}
                        onChange={(event) =>
                            form.setData('due_date', event.target.value)
                        }
                    />
                </label>
                <label className="sm:col-span-2">
                    Description (optional)
                    <textarea
                        className={fieldClass}
                        maxLength={16000}
                        rows={3}
                        value={form.data.description}
                        onChange={(event) =>
                            form.setData('description', event.target.value)
                        }
                    />
                </label>
            </fieldset>
            {form.hasErrors && (
                <ul
                    className="list-inside list-disc text-sm text-red-700"
                    role="alert"
                >
                    {Object.entries(form.errors).map(([field, error]) => (
                        <li key={field}>{error}</li>
                    ))}
                </ul>
            )}
            {requestError && (
                <p className="text-sm text-red-700" role="alert">
                    {requestError}
                </p>
            )}
            <div className="flex gap-3">
                <button
                    className="rounded-md bg-neutral-900 px-4 py-2 text-white disabled:opacity-60"
                    disabled={form.processing}
                    type="submit"
                >
                    {form.processing ? 'Saving…' : 'Save edit'}
                </button>
                <button
                    className="rounded-md border px-4 py-2"
                    disabled={form.processing}
                    onClick={onCancel}
                    type="button"
                >
                    Cancel
                </button>
            </div>
        </form>
    );
}

export default function Edits({
    edits,
    ...options
}: EditOptions & { edits: EditRecord[] }) {
    const [records, setRecords] = useState(edits);
    const [search, setSearch] = useState('');
    const [editor, setEditor] = useState<EditRecord | 'new' | null>(null);
    const [actionError, setActionError] = useState('');
    const [notice, setNotice] = useState('');
    const rowRequest = useHttp<{ is_paid?: boolean }, EditResponse>({});

    const term = search.trim().toLowerCase();
    const matchingEdits = records.filter((edit) =>
        [
            edit.title,
            edit.description,
            edit.project.name,
            edit.status,
            edit.priority,
        ].some((value) => value?.toLowerCase().includes(term)),
    );

    function saveToTable(saved: EditRecord) {
        setRecords((current) =>
            current.some((edit) => edit.id === saved.id)
                ? current.map((edit) => (edit.id === saved.id ? saved : edit))
                : [saved, ...current],
        );
    }

    async function changeRow(
        edit: EditRecord,
        method: 'patch' | 'delete',
        changes: { is_paid?: boolean } = {},
    ) {
        setActionError('');
        setNotice('');
        rowRequest.transform(() => changes);
        let failureMessage =
            method === 'delete'
                ? 'Could not delete the edit.'
                : 'Could not update the paid status.';

        try {
            await rowRequest.submit(method, `${apiUrl}/${edit.id}`, {
                onSuccess: (result) => {
                    if (method === 'delete') {
                        setRecords((current) =>
                            current.filter((record) => record.id !== edit.id),
                        );
                        setNotice('Edit deleted.');
                    } else {
                        saveToTable(result.data);
                        setNotice(
                            result.data.is_paid
                                ? 'Edit marked paid.'
                                : 'Edit marked unpaid.',
                        );
                    }
                },
                onError: () => setActionError(failureMessage),
                onHttpException: ({ status }) => {
                    if (method === 'delete' && status === 409) {
                        failureMessage =
                            'This edit cannot be deleted while it has attachments.';
                    }
                },
            });
        } catch {
            setActionError(failureMessage);
        }
    }

    const rowActionsDisabled = rowRequest.processing || editor !== null;

    return (
        <main className="mx-auto max-w-6xl px-6 py-12">
            <Head title="Edits" />
            <Link className="text-sm underline" href="/">
                Back to home
            </Link>
            <header className="mt-4 flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-3xl font-semibold">Edits</h1>
                <button
                    className="rounded-md bg-neutral-900 px-4 py-2 text-white disabled:opacity-60"
                    disabled={
                        rowActionsDisabled || options.projects.length === 0
                    }
                    onClick={() => setEditor('new')}
                    type="button"
                >
                    Add edit
                </button>
            </header>
            {options.projects.length === 0 && (
                <p className="mt-3 text-sm text-neutral-600">
                    Create a project before adding an edit.
                </p>
            )}
            {editor && (
                <EditForm
                    key={editor === 'new' ? 'new' : editor.id}
                    {...options}
                    edit={editor === 'new' ? null : editor}
                    onCancel={() => setEditor(null)}
                    onSaved={(saved) => {
                        saveToTable(saved);
                        setEditor(null);
                        setActionError('');
                        setNotice('Edit saved.');
                    }}
                />
            )}
            <label className="mt-6 block max-w-sm">
                Search edits
                <input
                    className={fieldClass}
                    type="search"
                    placeholder="Title, project, status…"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                />
            </label>
            {actionError && (
                <p className="mt-3 text-sm text-red-700" role="alert">
                    {actionError}
                </p>
            )}
            <p className="mt-3 text-sm text-neutral-600" role="status">
                {notice}
            </p>
            <div className="mt-4 overflow-x-auto rounded-md border">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-neutral-50">
                        <tr>
                            {[
                                'Title',
                                'Project',
                                'Priority',
                                'Status',
                                'Paid',
                                'Actions',
                            ].map((heading) => (
                                <th
                                    className="px-4 py-3"
                                    key={heading}
                                    scope="col"
                                >
                                    {heading}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {matchingEdits.map((edit) => (
                            <tr className="align-top" key={edit.id}>
                                <td className="px-4 py-3">
                                    <span className="font-medium">
                                        {edit.title}
                                    </span>
                                    <details className="mt-1 text-neutral-700">
                                        <summary className="cursor-pointer underline">
                                            View details
                                        </summary>
                                        <p className="mt-2 wrap-break-word whitespace-pre-wrap">
                                            {edit.description ??
                                                'No description.'}
                                        </p>
                                        <dl className="mt-2">
                                            <dt className="font-medium">
                                                Price
                                            </dt>
                                            <dd>{edit.price ?? '—'}</dd>
                                            <dt className="font-medium">
                                                Due date
                                            </dt>
                                            <dd>{edit.due_date ?? '—'}</dd>
                                        </dl>
                                        <Link
                                            className="mt-2 inline-block underline"
                                            href={`/edits/${edit.id}/attachments`}
                                        >
                                            Attachments
                                        </Link>
                                    </details>
                                </td>
                                <td className="px-4 py-3">
                                    {edit.project.name}
                                </td>
                                <td className="px-4 py-3">{edit.priority}</td>
                                <td className="px-4 py-3">{edit.status}</td>
                                <td className="px-4 py-3">
                                    <input
                                        aria-label={`Paid for ${edit.title}`}
                                        checked={edit.is_paid}
                                        disabled={rowActionsDisabled}
                                        type="checkbox"
                                        onChange={(event) =>
                                            void changeRow(edit, 'patch', {
                                                is_paid: event.target.checked,
                                            })
                                        }
                                    />
                                </td>
                                <td className="space-x-3 px-4 py-3 whitespace-nowrap">
                                    <button
                                        className="underline disabled:opacity-60"
                                        disabled={rowActionsDisabled}
                                        onClick={() => setEditor(edit)}
                                        type="button"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        className="text-red-700 underline disabled:opacity-60"
                                        disabled={rowActionsDisabled}
                                        type="button"
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    `Delete "${edit.title}"?`,
                                                )
                                            ) {
                                                void changeRow(edit, 'delete');
                                            }
                                        }}
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                        {matchingEdits.length === 0 && (
                            <tr>
                                <td
                                    className="px-4 py-6 text-neutral-600"
                                    colSpan={6}
                                >
                                    {records.length
                                        ? 'No edits match your search.'
                                        : 'No edits yet.'}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </main>
    );
}
