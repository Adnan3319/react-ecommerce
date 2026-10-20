export default function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p role="alert" className="mt-1.5 text-xs text-error-500">
            {message}
        </p>
    );
}
