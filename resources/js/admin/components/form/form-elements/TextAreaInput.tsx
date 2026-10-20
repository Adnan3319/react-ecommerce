import ComponentCard from '@admin/components/common/ComponentCard';
import TextArea from '@admin/components/form/input/TextArea';
import Label from '@admin/components/form/Label';
import { cn } from '@admin/utils';
import { useState } from 'react';

export default function TextAreaInput() {
    const [message, setMessage] = useState('');
    const [messageTwo, setMessageTwo] = useState('');
    return (
        <ComponentCard title="Textarea input field">
            <div className={cn('space-y-6')}>
                {/* Default TextArea */}
                <div>
                    <Label>Description</Label>
                    <TextArea
                        value={message}
                        onChange={(value) => setMessage(value)}
                        rows={6}
                    />
                </div>

                {/* Disabled TextArea */}
                <div>
                    <Label>Description</Label>
                    <TextArea rows={6} disabled />
                </div>

                {/* Error TextArea */}
                <div>
                    <Label>Description</Label>
                    <TextArea
                        rows={6}
                        value={messageTwo}
                        error
                        onChange={(value) => setMessageTwo(value)}
                        hint="Please enter a valid message."
                    />
                </div>
            </div>
        </ComponentCard>
    );
}
