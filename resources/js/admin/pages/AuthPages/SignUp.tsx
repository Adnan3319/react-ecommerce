import SignUpForm from "@admin/components/auth/SignUpForm";
import PageMeta from "@admin/components/common/PageMeta";
import AuthLayout from "./AuthPageLayout";

export default function SignUp() {
  return (
    <>
      <PageMeta
        title="React.js SignUp Dashboard | TailAdmin - React.js Admin Dashboard Template"
        description="This is React.js SignUp Tables Dashboard page for TailAdmin - React.js Tailwind CSS Admin Dashboard Template"
      />
      <AuthLayout>
        <SignUpForm />
      </AuthLayout>
    </>
  );
}
