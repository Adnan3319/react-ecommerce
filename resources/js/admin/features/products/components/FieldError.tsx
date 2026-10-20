export default function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p role="alert" className="text-error-500 mt-1.5 text-xs">
            {message}
        </p>
    );
}
