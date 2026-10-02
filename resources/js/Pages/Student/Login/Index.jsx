import { useEffect } from 'react';
import Checkbox from '@/Components/Checkbox';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Login({ error, canResetPassword }) {
    const { appSetting } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        nisn: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();

        post(route('student.login'));
    };

    return (
        <GuestLayout>
            <Head title={`${appSetting?.app_name || 'SIBITI'} - Login Siswa`} />

            <div className="mb-7">
                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Ruang siswa</p>
                <h2 className="mt-2 text-2xl font-bold tracking-tight text-slate-900">Masuk ke akunmu</h2>
                <p className="mt-2 text-sm leading-6 text-slate-500">Gunakan NISN dan password untuk melihat ujian yang tersedia.</p>
            </div>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="nisn" value="NISN" className="text-slate-700" />

                    <TextInput
                        id="nisn"
                        type="text"
                        inputMode="numeric"
                        maxLength={19}
                        name="nisn"
                        value={data.nisn}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('nisn', e.target.value)}
                    />

                    <InputError message={errors.nisn} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" className="text-slate-700" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="block mt-4">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        <span className="ms-2 text-sm text-slate-500">Ingat perangkat ini</span>
                    </label>
                </div>

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton className="w-full justify-center rounded-xl bg-teal-700 py-3 normal-case tracking-normal shadow-sm hover:bg-teal-800" disabled={processing}>
                        {processing ? 'Memproses...' : 'Masuk sebagai siswa'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
