import Label from "@admin/components/form/Label";
import Checkbox from "@admin/components/form/input/Checkbox";
import Input from "@admin/components/form/input/InputField";
import Button from "@admin/components/ui/button/Button";
import { EyeCloseIcon, EyeIcon } from "@admin/icons";
import { store } from "@/routes/admin/login";
import { useForm } from "@inertiajs/react";
import { useState } from "react";
import type { FormEvent } from "react";

export default function SignInForm() {
  const [showPassword, setShowPassword] = useState(false);
  const { data, setData, post, processing, errors } = useForm({
    username: "",
    password: "",
    remember: false,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    post(store().url);
  };

  return (
    <div className="flex flex-1 flex-col">
      <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
        <div className="mb-5 sm:mb-8">
          <h1 className="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
            Admin Sign In
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Enter your username and password to sign in!
          </p>
        </div>

        <form onSubmit={submit}>
          <div className="space-y-6">
            <div>
              <Label>
                Username <span className="text-error-500">*</span>
              </Label>
              <Input
                name="username"
                placeholder="Enter your username"
                value={data.username}
                onChange={(e) => setData("username", e.target.value)}
              />
              {errors.username && (
                <p className="mt-1.5 text-sm text-error-500">{errors.username}</p>
              )}
            </div>

            <div>
              <Label>
                Password <span className="text-error-500">*</span>
              </Label>
              <div className="relative">
                <Input
                  name="password"
                  type={showPassword ? "text" : "password"}
                  placeholder="Enter your password"
                  value={data.password}
                  onChange={(e) => setData("password", e.target.value)}
                />
                <span
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute inset-e-4 top-1/2 z-30 -translate-y-1/2 cursor-pointer"
                >
                  {showPassword ? (
                    <EyeIcon className="size-5 fill-gray-500 dark:fill-gray-400" />
                  ) : (
                    <EyeCloseIcon className="size-5 fill-gray-500 dark:fill-gray-400" />
                  )}
                </span>
              </div>
              {errors.password && (
                <p className="mt-1.5 text-sm text-error-500">{errors.password}</p>
              )}
            </div>

            <div className="flex items-center gap-3">
              <Checkbox
                checked={data.remember}
                onChange={(v) => setData("remember", v)}
              />
              <span className="block text-theme-sm font-normal text-gray-700 dark:text-gray-400">
                Keep me logged in
              </span>
            </div>

            <div>
              <Button className="w-full" size="sm" disabled={processing}>
                {processing ? "Signing in..." : "Sign in"}
              </Button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}