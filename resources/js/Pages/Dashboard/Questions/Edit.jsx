import QuestionForm from './QuestionForm';
import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Edit({ exam, question }) {
    return <QuestionForm exam={exam} question={question} />;
}

Edit.layout = (page) => <DashboardLayout>{page}</DashboardLayout>;
