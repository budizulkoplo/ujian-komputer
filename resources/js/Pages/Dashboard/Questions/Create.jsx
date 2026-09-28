import QuestionForm from './QuestionForm';
import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Create({ exam }) {
    return <QuestionForm exam={exam} />;
}

Create.layout = (page) => <DashboardLayout>{page}</DashboardLayout>;
