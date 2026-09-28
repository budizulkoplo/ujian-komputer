import { Head, useForm, usePage } from '@inertiajs/react';
import { IconDeviceFloppy, IconSettings } from '@tabler/icons-react';
import Button from '@/Components/Dashboard/Button';
import Card from '@/Components/Dashboard/Card';
import DashboardLayout from '@/Layouts/DashboardLayout';
import Input from '@/Components/Dashboard/Input';
import Textarea from '@/Components/Dashboard/TextArea';

export default function Index({ setting }) {
    const { errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        app_name: setting.app_name || '',
        school_name: setting.school_name || '',
        school_address: setting.school_address || '',
        cheat_limit: setting.cheat_limit ?? 3,
        school_logo: null,
    });

    const saveSetting = (event) => {
        event.preventDefault();
        post(route('settings.update'), { forceFormData: true });
    };

    return (
        <>
            <Head title="Setting Aplikasi" />
            <Card
                title="Setting Aplikasi"
                icon={<IconSettings size={20} strokeWidth={1.5} />}
                form={saveSetting}
                footer={<Button type="submit" label={processing ? 'Menyimpan...' : 'Simpan'} icon={<IconDeviceFloppy size={20} strokeWidth={1.5} />} added />}
            >
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Nama Aplikasi" type="text" value={data.app_name} onChange={(event) => setData('app_name', event.target.value)} errors={errors.app_name} required />
                    <Input label="Nama Sekolah" type="text" value={data.school_name} onChange={(event) => setData('school_name', event.target.value)} errors={errors.school_name} required />
                </div>
                <div className="mt-4">
                    <Textarea label="Alamat Sekolah" value={data.school_address} onChange={(event) => setData('school_address', event.target.value)} errors={errors.school_address} required />
                </div>
                <div className="mt-4 max-w-sm">
                    <Input label="Batas kecurangan sebelum ujian dikunci" type="number" min="1" max="100" value={data.cheat_limit} onChange={(event) => setData('cheat_limit', event.target.value)} errors={errors.cheat_limit} required />
                </div>
                <div className="mt-4 flex flex-col gap-2">
                    <label className="text-gray-600 text-sm">Logo Sekolah</label>
                    <input type="file" accept="image/*" onChange={(event) => setData('school_logo', event.target.files[0])} className="w-full rounded-md border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" />
                    {setting.school_logo && <img src={`/storage/${setting.school_logo}`} alt="Logo sekolah" className="h-20 w-20 object-contain" />}
                    {errors.school_logo && <small className="text-xs text-red-500">{errors.school_logo}</small>}
                </div>
            </Card>
        </>
    );
}

Index.layout = page => <DashboardLayout children={page} />;