import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    });

    function signIn(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        post('/login', { replace: true, onFinish: () => reset('password') });
    }

    return (
        <main className="mx-auto max-w-sm px-6 py-16">
            <Head title="Admin sign-in" />
            <h1 className="text-2xl font-semibold">Admin sign-in</h1>
            <form className="mt-6 space-y-4" onSubmit={signIn}>
                <div>
                    <label
                        className="block text-sm font-medium"
                        htmlFor="email"
                    >
                        Email
                    </label>
                    <input
                        autoComplete="username"
                        className="mt-1 w-full rounded-md border px-3 py-2"
                        id="email"
                        name="email"
                        type="email"
                        required
                        value={data.email}
                        onChange={(event) =>
                            setData('email', event.target.value)
                        }
                    />
                    {errors.email && (
                        <p className="mt-1 text-sm text-red-700" role="alert">
                            {errors.email}
                        </p>
                    )}
                </div>
                <div>
                    <label
                        className="block text-sm font-medium"
                        htmlFor="password"
                    >
                        Password
                    </label>
                    <input
                        autoComplete="current-password"
                        className="mt-1 w-full rounded-md border px-3 py-2"
                        id="password"
                        name="password"
                        type="password"
                        required
                        value={data.password}
                        onChange={(event) =>
                            setData('password', event.target.value)
                        }
                    />
                    {errors.password && (
                        <p className="mt-1 text-sm text-red-700" role="alert">
                            {errors.password}
                        </p>
                    )}
                </div>
                <button
                    className="rounded-md bg-neutral-900 px-4 py-2 text-white disabled:opacity-60"
                    disabled={processing}
                    type="submit"
                >
                    Sign in
                </button>
            </form>
        </main>
    );
}
