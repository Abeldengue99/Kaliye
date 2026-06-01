#!/usr/bin/env bash

# Script de manutenção para corrigir notificações
# Executa via curl no servidor

curl -s "http://192.168.0.195/kaliye/index.php" -d "action=fix_notifications&token=test" | head -20

echo "Tentativa alternativa..."
curl -s "http://localhost/kaliye/index.php" -d "fix=1" | head -20
