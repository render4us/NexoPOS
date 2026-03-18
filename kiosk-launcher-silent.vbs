' ══════════════════════════════════════════════════════════════════════
' KIOSK LAUNCHER SILENT — Executa o kiosk-launcher.bat sem janela preta
' Use este arquivo como atalho na area de trabalho ou na Inicializacao.
' ══════════════════════════════════════════════════════════════════════

Dim oShell
Set oShell = CreateObject("WScript.Shell")

' Caminho do .bat (assume mesma pasta deste .vbs)
Dim batPath
batPath = Replace(WScript.ScriptFullName, WScript.ScriptName, "") & "kiosk-launcher.bat"

' 0 = janela oculta
oShell.Run Chr(34) & batPath & Chr(34), 0, False

Set oShell = Nothing
