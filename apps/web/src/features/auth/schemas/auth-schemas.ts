import { z } from 'zod';

/** Mirrors the API's password policy (docs/06 §7); the API stays authoritative. */
export const PASSWORD_MIN_LENGTH = 12;
const PASSWORD_MAX_LENGTH = 1024;

const email = z.string().trim().min(1, 'Enter your e-mail address.').email('Enter a valid e-mail address.').max(255);

export const loginSchema = z.object({
  email,
  password: z.string().min(1, 'Enter your password.').max(PASSWORD_MAX_LENGTH),
});

export const forgotPasswordSchema = z.object({ email });

export const resetPasswordSchema = z
  .object({
    password: z
      .string()
      .min(PASSWORD_MIN_LENGTH, `Use at least ${PASSWORD_MIN_LENGTH} characters.`)
      .max(PASSWORD_MAX_LENGTH),
    password_confirmation: z.string(),
  })
  .refine((values) => values.password === values.password_confirmation, {
    path: ['password_confirmation'],
    message: 'The passwords do not match.',
  });

export type LoginValues = z.infer<typeof loginSchema>;
export type ForgotPasswordValues = z.infer<typeof forgotPasswordSchema>;
export type ResetPasswordValues = z.infer<typeof resetPasswordSchema>;
