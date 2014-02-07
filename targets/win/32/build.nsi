!define PRODUCT_NAME "Dynacase Offline"
!ifndef PRODUCT_VERSION
    !error "PRODUCT_VERSION is not defined!"
!endif
!define PRODUCT_INTERNAL_NAME "dynacase-offline"
!define PRODUCT_WIN_VERSION "1.0.0.0"

!define MESSAGEWINDOW_NAME "${PRODUCT_NAME}MessageWindow"

!define HKEY_ROOT "HKLM"
!define UN_KEY "Software\Microsoft\Windows\CurrentVersion\Uninstall\${PRODUCT_NAME}"

!define LICENSE_PATH "dist/LICENSE.txt"

!define INSTALLER_NAME "${PRODUCT_INTERNAL_NAME}-setup.exe"
!define TMP_UNINSTALL_EXE "${PRODUCT_INTERNAL_NAME}_uninstall.exe"

;--------------------------------
;Variables

; The name of the product installed
Name "${PRODUCT_NAME} ${PRODUCT_VERSION}"

; The file to write
OutFile "${INSTALLER_NAME}"

SetCompressor /final /solid lzma
ShowInstDetails show
ShowUninstDetails show

; The default installation directory
InstallDir "$PROGRAMFILES\${PRODUCT_NAME}"

; Request application privileges for Windows Vista
RequestExecutionLevel admin

Var Shortcuts_Dialog
Var Shortcuts_Label
Var Shortcuts_SM_Checkbox
Var Shortcuts_SM_Checkbox_State
Var Shortcuts_D_Checkbox
Var Shortcuts_D_Checkbox_State
Var Shortcuts_ALL_Checkbox
Var Shortcuts_ALL_Checkbox_State

Var Previous_Uninstall
Var Previous_Uninstall_dir
Var TempUninstallPath

!include "MUI2.nsh"
!include "FileFunc.nsh"

VIProductVersion "${PRODUCT_WIN_VERSION}"

VIAddVersionKey "ProductName"     "${PRODUCT_NAME}"
;VIAddVersionKey "CompanyName"     "${CompanyName}"
;VIAddVersionKey "LegalTrademarks" "${BrandShortName} is a Trademark of"
;VIAddVersionKey "LegalCopyright"  "${CompanyName}"
VIAddVersionKey "LegalCopyright"  ""
VIAddVersionKey "FileVersion"     "${PRODUCT_VERSION}"
VIAddVersionKey "ProductVersion"  "${PRODUCT_VERSION}"
VIAddVersionKey "FileDescription" "${PRODUCT_NAME} Installer"
VIAddVersionKey "OriginalFilename" "${INSTALLER_NAME}"

!define MUI_FINISHPAGE_RUN "$INSTDIR\${PRODUCT_INTERNAL_NAME}.exe"

;--------------------------------
; Pages

    !insertmacro MUI_PAGE_WELCOME
    !insertmacro MUI_PAGE_LICENSE "${LICENSE_PATH}"
    !insertmacro MUI_PAGE_DIRECTORY
    Page custom onShortcutsPageCreate
    !insertmacro MUI_PAGE_INSTFILES
    !insertmacro MUI_PAGE_FINISH
    
    !insertmacro MUI_UNPAGE_WELCOME
    !insertmacro MUI_UNPAGE_CONFIRM
    !insertmacro MUI_UNPAGE_INSTFILES
    
    
    !insertmacro MUI_LANGUAGE "English"
    !insertmacro MUI_LANGUAGE "French"

    !include ./l10n/fr.nsh
    !include ./l10n/en_US.nsh


;--------------------------------

Function .onInit
    ; an eventual previous version of the app should not be currently running.
    ; Abort if any.
    ; Explanation, when the application is running, a window with the className
    ; productnameMessageWindow exists
    FindWindow $0 "${MESSAGEWINDOW_NAME}"
    StrCmp $0 0 +3
        MessageBox MB_OK|MB_ICONEXCLAMATION "${PRODUCT_NAME} is running. Please close it first" /SD IDOK
        Abort
    
    StrCpy $Shortcuts_SM_Checkbox_State 1
    StrCpy $Shortcuts_D_Checkbox_State 1
    StrCpy $Shortcuts_ALL_Checkbox_State 0
FunctionEnd

Function un.onInit
    ; see Function .onInit
    FindWindow $0 "${MESSAGEWINDOW_NAME}"
    StrCmp $0 0 +3
        MessageBox MB_OK|MB_ICONEXCLAMATION "${PRODUCT_NAME} is running. Please close it first" /SD IDOK
        Abort
FunctionEnd

; custom page creation, for the shortcuts installation, using nsDialog
Function onShortcutsPageCreate
    !insertmacro MUI_HEADER_TEXT $(l10n_SHORTCUTS_PAGE_TITLE) \
        $(l10n_SHORTCUTS_PAGE_SUBTITLE)
    
    nsDialogs::Create 1018
    Pop $Shortcuts_Dialog
    
    ${If} $Shortcuts_Dialog == error
        Abort
    ${EndIf}

    ${NSD_CreateLabel} 0 6 100% 12u $(l10n_CREATE_ICONS_DESC)
    Pop $Shortcuts_Label

    ${NSD_CreateCheckbox} 15u 20u 100% 10u $(l10n_ICONS_STARTMENU)
    Pop $Shortcuts_SM_Checkbox
    GetFunctionAddress $0 OnSMCheckbox
    nsDialogs::OnClick $Shortcuts_SM_Checkbox $0
    
    ${If} $Shortcuts_SM_Checkbox_State == ${BST_CHECKED}
        ${NSD_Check} $Shortcuts_SM_Checkbox
    ${EndIf}

    ${NSD_CreateCheckbox} 15u 40u 100% 10u $(l10n_ICONS_DESKTOP)
    Pop $Shortcuts_D_Checkbox
    GetFunctionAddress $0 OnDCheckbox
    nsDialogs::OnClick $Shortcuts_D_Checkbox $0

    ${If} $Shortcuts_D_Checkbox_State == ${BST_CHECKED}
        ${NSD_Check} $Shortcuts_D_Checkbox
    ${EndIf}

    ${NSD_CreateCheckbox} 15u 60u 100% 10u $(l10n_SHORTCUTS_ALL)
    Pop $Shortcuts_ALL_Checkbox
    GetFunctionAddress $0 OnALLCheckbox
    nsDialogs::OnClick $Shortcuts_ALL_Checkbox $0

    ${If} $Shortcuts_ALL_Checkbox_State == ${BST_CHECKED}
        ${NSD_Check} $Shortcuts_ALL_Checkbox
    ${EndIf}

    nsDialogs::Show
FunctionEnd

; event when the Start Menu shortcut is (un)checked in the custom page
Function OnSMCheckbox
    ${NSD_GetState} $Shortcuts_SM_Checkbox $Shortcuts_SM_Checkbox_State
    Pop $0 # HWND
FunctionEnd

; event when the Desktop shortcut is (un)checked in the custom page
Function OnDCheckbox
    ${NSD_GetState} $Shortcuts_D_Checkbox $Shortcuts_D_Checkbox_State
    Pop $0 # HWND
FunctionEnd

; event when the "Apply to all users" is (un)checked in the custom page
Function OnALLCheckbox
    ${NSD_GetState} $Shortcuts_ALL_Checkbox $Shortcuts_ALL_Checkbox_State
    Pop $0 # HWND
FunctionEnd

Function WriteUninstallReg
    WriteRegStr "${HKEY_ROOT}" "${UN_KEY}" "DisplayName" \
        "${PRODUCT_NAME} (${PRODUCT_VERSION})"
    WriteRegStr "${HKEY_ROOT}" "${UN_KEY}" "UninstallString" \
        "$INSTDIR\uninstall.exe"
    WriteRegStr "${HKEY_ROOT}" "${UN_KEY}" "QuietUninstallString" \
        "$INSTDIR\uninstall.exe /S"
    WriteRegStr "${HKEY_ROOT}" "${UN_KEY}" "InstallLocation" \
        "$INSTDIR"
    WriteRegStr "${HKEY_ROOT}" "${UN_KEY}" "DisplayIcon" \
        "$INSTDIR\${PRODUCT_INTERNAL_NAME}.exe"
    WriteRegStr "${HKEY_ROOT}" "${UN_KEY}" "DisplayVersion" \
        "${PRODUCT_VERSION}"
    
    ${GetSize} "$INSTDIR" "/S=0K" $0 $1 $2
    IntFmt $0 "0x%08X" $0
    WriteRegDWORD "${HKEY_ROOT}" "${UN_KEY}" "EstimatedSize" "$0"
FunctionEnd

; The stuff to install
Section ""
    ; uninstall an eventual previous installation
    ReadRegStr $Previous_Uninstall "${HKEY_ROOT}" "${UN_KEY}" "UninstallString"
    ClearErrors
    ${If} $Previous_Uninstall != ""
        StrCpy $Previous_Uninstall_dir $Previous_Uninstall
        ${GetParent} $Previous_Uninstall $Previous_Uninstall_dir
        
        IfFileExists "$Previous_Uninstall" myUninstallPrevious myInstall
    ${Else}
        goto myInstall
    ${EndIf}
  
    myUninstallPrevious:
        ; copy the previous uninstaller into TEMP
        ClearErrors
        StrCpy $TempUninstallPath "$TEMP\${TMP_UNINSTALL_EXE}"
        CopyFiles /SILENT "$Previous_Uninstall" "$TempUninstallPath"
        IfErrors myInstall
        
        ClearErrors
        ExecWait '"$TempUninstallPath" /S _?=$Previous_Uninstall_dir'
        
        ClearErrors
        Delete "$TempUninstallPath"
        
        ;MessageBox MB_OK "UNINSTALL: finished"
    
    myInstall:
        SetOutPath $INSTDIR
        
        ; copy the files
        File /r dist\*
        
        WriteUninstaller "uninstall.exe"
        
        Call WriteUninstallReg
	
        ${If} $Shortcuts_ALL_Checkbox_State == ${BST_CHECKED}
            SetShellVarContext all
        ${EndIf}
	
        ${If} $Shortcuts_SM_Checkbox_State == ${BST_CHECKED}
            CreateDirectory "$SMPROGRAMS\${PRODUCT_NAME}"
            CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\${PRODUCT_NAME}.lnk" \
                "$INSTDIR\${PRODUCT_INTERNAL_NAME}.exe" \
                "" "$INSTDIR\${PRODUCT_INTERNAL_NAME}.ico"
            ${If} $Shortcuts_ALL_Checkbox_State == ${BST_CHECKED}
                ; If "Apply to all users" is selected, then
                ; the Uninstall.lnk is only created in the admins
                ; programs list, and not in everyones programs list
                SetShellVarContext current
                CreateDirectory "$SMPROGRAMS\${PRODUCT_NAME}"
                CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\Uninstall.lnk" \
                    "$INSTDIR\uninstall.exe"
                SetShellVarContext all
            ${Else}
                CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\Uninstall.lnk" \
                    "$INSTDIR\uninstall.exe"
            ${EndIf}
        ${EndIf}
        
        ${If} $Shortcuts_D_Checkbox_State == ${BST_CHECKED}
            CreateShortCut "$DESKTOP\${PRODUCT_NAME}.lnk" \
                "$INSTDIR\${PRODUCT_INTERNAL_NAME}.exe" \
		"" "$INSTDIR\${PRODUCT_INTERNAL_NAME}.ico"
        ${EndIf}
SectionEnd

;--------------------------------
; Uninstaller

Section "Uninstall"
    ; MessageBox MB_OK|MB_ICONEXCLAMATION "$INSTDIR" /SD IDOK
    ; Remove installed files and uninstaller
    !include ./uninstall_files.nsi
    Delete "$INSTDIR\uninstall.exe"
    
    ; remove installed directories
    !include ./uninstall_dirs.nsi
    RMDir /r "$INSTDIR\extensions"
    
    ; Remove shortcuts, if any
    SetShellVarContext all
    Delete "$SMPROGRAMS\${PRODUCT_NAME}\${PRODUCT_NAME}.lnk"
    RMDir  "$SMPROGRAMS\${PRODUCT_NAME}"
    Delete "$DESKTOP\${PRODUCT_NAME}.lnk"
    
    SetShellVarContext current
    Delete "$SMPROGRAMS\${PRODUCT_NAME}\${PRODUCT_NAME}.lnk"
    Delete "$SMPROGRAMS\${PRODUCT_NAME}\Uninstall.lnk"
    RMDir  "$SMPROGRAMS\${PRODUCT_NAME}"
    Delete "$DESKTOP\${PRODUCT_NAME}.lnk"
    ;TODO remove eventual quicklaunch Too
    
    ; Remove the installation directory used (if empty)
    RMDir "$INSTDIR"
  
    ; and delete the registry key for uninstall
    DeleteRegKey "${HKEY_ROOT}" "${UN_KEY}"
SectionEnd
