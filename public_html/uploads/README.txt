# ESTRUTURA DE ARQUIVOS DO USUÁRIO

Esta pasta contém os arquivos de cada usuário de forma organizada.

## Organização:

```
uploads/users/
├── {user_id_1}/
│   ├── profile/          # Foto de perfil do usuário
│   ├── kyc/              # Documentos de verificação de identidade
│   ├── transactions/     # Comprovantes de transações
│   └── other/            # Outros documentos
│
├── {user_id_2}/
│   ├── profile/
│   ├── kyc/
│   ├── transactions/
│   └── other/
│
└── ...
```

## Exemplo com usuário ID 42:

```
uploads/users/42/
├── profile/
│   └── avatar_1234567890.jpg
├── kyc/
│   ├── front_1234567890_abc123.jpg
│   ├── back_1234567891_def456.jpg
│   └── selfie_1234567892_ghi789.jpg
├── transactions/
│   ├── proof_1234567893_jkl012.pdf
│   └── proof_1234567894_mno345.jpg
└── other/
```

## Benefícios:

✅ Escalável para milhões de usuários
✅ Fácil encontrar arquivos de um usuário específico
✅ Organização por tipo de documento
✅ Backup e manutenção simplificados
✅ Segurança - cada usuário tem sua pasta isolada

## Segurança:

- Todas as pastas têm permissão 755
- .htaccess protege contra execução de PHP
- Apenas imagens e PDFs são permitidos
