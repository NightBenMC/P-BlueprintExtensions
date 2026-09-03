import React, { useState, useCallback, useEffect, useRef } from 'react';
import http from '@/api/http';

interface FormErrors {
    email?: string[];
    username?: string[];
    first_name?: string[];
    last_name?: string[];
    password?: string[];
    password_confirmation?: string[];
    general?: string;
}

const RegisterForm: React.FC = () => {
    const [formData, setFormData] = useState({
        email: '',
        username: '',
        first_name: '',
        last_name: '',
        password: '',
        password_confirmation: '',
        recaptcha_token: '',
    });
    const [errors, setErrors] = useState<FormErrors>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [success, setSuccess] = useState(false);
    const [cooldown, setCooldown] = useState(0);
    const cooldownRef = useRef<ReturnType<typeof setInterval> | null>(null);

    useEffect(() => {
        return () => {
            if (cooldownRef.current) clearInterval(cooldownRef.current);
        };
    }, []);

    const startCooldown = useCallback((seconds: number) => {
        setCooldown(seconds);
        if (cooldownRef.current) clearInterval(cooldownRef.current);
        cooldownRef.current = setInterval(() => {
            setCooldown((prev) => {
                if (prev <= 1) {
                    if (cooldownRef.current) clearInterval(cooldownRef.current);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);
    }, []);

    const handleChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
        setErrors((prev) => ({ ...prev, [name]: undefined, general: undefined }));
    }, []);

    const handleSubmit = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();
        if (isSubmitting || cooldown > 0) return;

        setErrors({});
        setIsSubmitting(true);

        try {
            await http.post('/extensions/fileflow/register', formData);
            setSuccess(true);
        } catch (err: any) {
            const data = err?.response?.data;
            const status = err?.response?.status;

            if (status === 429) {
                const retryAfter = data?.retry_after || 60;
                startCooldown(retryAfter);
                setErrors({ general: data?.error || 'Too many attempts. Please wait.' });
            } else if (status === 422 && data?.errors) {
                setErrors(data.errors);
            } else {
                setErrors({ general: data?.error || 'Registration failed. Please try again.' });
            }
        } finally {
            setIsSubmitting(false);
        }
    }, [formData, isSubmitting, cooldown, startCooldown]);

    if (success) {
        return (
            <div style={{
                maxWidth: '440px',
                margin: '40px auto',
                padding: '32px',
                background: 'rgba(26, 32, 44, 0.8)',
                borderRadius: '12px',
                border: '1px solid rgba(72, 187, 120, 0.3)',
                textAlign: 'center',
            }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>{'\u2714'}</div>
                <h2 style={{ color: '#68d391', fontSize: '20px', marginBottom: '8px' }}>Account Created</h2>
                <p style={{ color: '#a0aec0', fontSize: '14px', marginBottom: '20px' }}>
                    Your account has been created successfully. You can now log in.
                </p>
                <a
                    href="/auth/login"
                    style={{
                        display: 'inline-block',
                        padding: '10px 24px',
                        background: '#3182ce',
                        color: '#fff',
                        borderRadius: '6px',
                        textDecoration: 'none',
                        fontSize: '14px',
                        fontWeight: 500,
                    }}
                >
                    Go to Login
                </a>
            </div>
        );
    }

    const inputStyle: React.CSSProperties = {
        width: '100%',
        padding: '10px 14px',
        background: 'rgba(255,255,255,0.05)',
        border: '1px solid rgba(255,255,255,0.1)',
        borderRadius: '6px',
        color: '#e2e8f0',
        fontSize: '14px',
        outline: 'none',
        boxSizing: 'border-box',
        transition: 'border-color 0.15s',
    };

    const inputErrorStyle: React.CSSProperties = {
        ...inputStyle,
        borderColor: '#f56565',
    };

    const labelStyle: React.CSSProperties = {
        display: 'block',
        color: '#a0aec0',
        fontSize: '13px',
        marginBottom: '4px',
        fontWeight: 500,
    };

    const errorTextStyle: React.CSSProperties = {
        color: '#fc8181',
        fontSize: '12px',
        marginTop: '4px',
    };

    return (
        <div style={{
            maxWidth: '440px',
            margin: '40px auto',
            padding: '32px',
            background: 'rgba(26, 32, 44, 0.8)',
            borderRadius: '12px',
            border: '1px solid rgba(255,255,255,0.06)',
            backdropFilter: 'blur(12px)',
        }}>
            <h2 style={{
                color: '#e2e8f0',
                fontSize: '22px',
                fontWeight: 600,
                marginBottom: '4px',
                textAlign: 'center',
            }}>
                Create Account
            </h2>
            <p style={{
                color: '#718096',
                fontSize: '13px',
                textAlign: 'center',
                marginBottom: '24px',
            }}>
                Fill in the form below to register
            </p>

            {errors.general && (
                <div style={{
                    padding: '10px 14px',
                    background: 'rgba(245, 101, 101, 0.1)',
                    border: '1px solid rgba(245, 101, 101, 0.3)',
                    borderRadius: '6px',
                    color: '#fc8181',
                    fontSize: '13px',
                    marginBottom: '16px',
                }}>
                    {errors.general}
                </div>
            )}

            <form onSubmit={handleSubmit}>
                <div style={{ display: 'flex', gap: '12px', marginBottom: '14px' }}>
                    <div style={{ flex: 1 }}>
                        <label style={labelStyle}>First Name</label>
                        <input
                            type="text"
                            name="first_name"
                            value={formData.first_name}
                            onChange={handleChange}
                            style={errors.first_name ? inputErrorStyle : inputStyle}
                            required
                            maxLength={100}
                        />
                        {errors.first_name && <p style={errorTextStyle}>{errors.first_name[0]}</p>}
                    </div>
                    <div style={{ flex: 1 }}>
                        <label style={labelStyle}>Last Name</label>
                        <input
                            type="text"
                            name="last_name"
                            value={formData.last_name}
                            onChange={handleChange}
                            style={errors.last_name ? inputErrorStyle : inputStyle}
                            required
                            maxLength={100}
                        />
                        {errors.last_name && <p style={errorTextStyle}>{errors.last_name[0]}</p>}
                    </div>
                </div>

                <div style={{ marginBottom: '14px' }}>
                    <label style={labelStyle}>Username</label>
                    <input
                        type="text"
                        name="username"
                        value={formData.username}
                        onChange={handleChange}
                        style={errors.username ? inputErrorStyle : inputStyle}
                        required
                        minLength={3}
                        maxLength={32}
                        pattern="^[a-zA-Z0-9_]+$"
                        title="Letters, numbers, and underscores only"
                    />
                    {errors.username && <p style={errorTextStyle}>{errors.username[0]}</p>}
                </div>

                <div style={{ marginBottom: '14px' }}>
                    <label style={labelStyle}>Email</label>
                    <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        style={errors.email ? inputErrorStyle : inputStyle}
                        required
                        maxLength={255}
                    />
                    {errors.email && <p style={errorTextStyle}>{errors.email[0]}</p>}
                </div>

                <div style={{ marginBottom: '14px' }}>
                    <label style={labelStyle}>Password</label>
                    <input
                        type="password"
                        name="password"
                        value={formData.password}
                        onChange={handleChange}
                        style={errors.password ? inputErrorStyle : inputStyle}
                        required
                        minLength={8}
                        maxLength={255}
                    />
                    {errors.password && <p style={errorTextStyle}>{errors.password[0]}</p>}
                </div>

                <div style={{ marginBottom: '20px' }}>
                    <label style={labelStyle}>Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        value={formData.password_confirmation}
                        onChange={handleChange}
                        style={errors.password_confirmation ? inputErrorStyle : inputStyle}
                        required
                    />
                    {errors.password_confirmation && <p style={errorTextStyle}>{errors.password_confirmation[0]}</p>}
                </div>

                <button
                    type="submit"
                    disabled={isSubmitting || cooldown > 0}
                    style={{
                        width: '100%',
                        padding: '12px',
                        background: isSubmitting || cooldown > 0 ? '#2d3748' : '#3182ce',
                        color: isSubmitting || cooldown > 0 ? '#718096' : '#fff',
                        border: 'none',
                        borderRadius: '6px',
                        fontSize: '14px',
                        fontWeight: 600,
                        cursor: isSubmitting || cooldown > 0 ? 'not-allowed' : 'pointer',
                        transition: 'background 0.15s',
                    }}
                >
                    {isSubmitting
                        ? 'Creating Account...'
                        : cooldown > 0
                        ? `Please wait ${cooldown}s`
                        : 'Create Account'}
                </button>

                <p style={{
                    textAlign: 'center',
                    marginTop: '16px',
                    fontSize: '13px',
                    color: '#718096',
                }}>
                    Already have an account?{' '}
                    <a href="/auth/login" style={{ color: '#63b3ed', textDecoration: 'none' }}>
                        Log in
                    </a>
                </p>
            </form>
        </div>
    );
};

export default RegisterForm;
