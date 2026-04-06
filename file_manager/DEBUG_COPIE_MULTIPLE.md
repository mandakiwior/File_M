# Guide de Débogage - Copie/Coupe/Collage Multiple

## Problème Identifié et Analysé

### Symptômes possibles:
1. **Problème : Copie/Coupe de plusieurs éléments ne fonctionne pas**
   - Sélectionner 5 fichiers → Copier → Coller → Seul 1 fichier est collé
   - Ou message "Aucun élément sélectionné"

### Causes Potentielles:

#### 1. **Problème de Sélection entre les Vues**
- Les vues (Détails, Liste, Icônes) gèrent la sélection différemment
- Détails: checkboxes `.file-checkbox:checked`
- Liste: classe `.selected` sur `.list-item`
- Icônes: classe `.selected` sur `.icon-item`

**Solution** ✅ Implémentée:
- `getAllSelectedItems()` maintenant check uniquement la vue active
- Change de vue = réinitialise les handlers de sélection

#### 2. **Persistent du Clipboard dans localStorage**
- Avant: stocké uniquement en variable JavaScript (perdus au rechargement)
- Maintenant: sauvegardé dans `localStorage` pour persistance

**Solution** ✅ Implémentée:
- `saveClipboard()` persiste les données
- Lecture depuis `localStorage.getItem('lundrive_clipboard')`

#### 3. **Payload JSON Vide ou Incorrecte**
- Le serveur (`paste.php`) pourrait recevoir un array vide

**Solution** ✅ Implémentée:
- Débogage détaillé côté client et serveur
- Logs dans la console et les fichiers d'erreur

---

## Guide de Test Pas à Pas

### Étape 1: Ouvrir la Console Navigateur
```
F12 → Onglet "Console"
```

### Étape 2: Vider les Anciens Données
```javascript
// Dans la console:
localStorage.clear();
location.reload();
```

### Étape 3: Tester la Sélection Multiple

1. **Mode Détails** (Vue par défaut):
   - Cocher 3-5 checkboxes
   - Vérifier: footer affiche "X éléments sélectionnés" ✓
   - Appeler dans la console: `debugClipboard()`
   - Vérifier que "Éléments sélectionnés: [Array(X)]" affiche bien X éléments

2. **Mode Liste**:
   - Cliquer sur 3-5 éléments (avec le curseur pointer)
   - Vérifier: footer affiche "X éléments sélectionnés" ✓
   - Appeler: `debugClipboard()`
   - Vérifier que la sélection fonctionne

3. **Mode Icônes**:
   - Cliquer sur 3-5 icônes
   - Vérifier: footer affiche "X éléments sélectionnés" ✓
   - Appeler: `debugClipboard()`

### Étape 4: Tester Copier

```javascript
// Sélectionner 5 fichiers
// Cliquer sur "Copier"
// Dans la console, vous devriez voir:
// "Copie - Éléments sélectionnés: [Array(5)]"
// "Copie - Clipboard sauvegardé: [Array(5)]"
// "Copie - localStorage (lundrive_clipboard): "[\"file_1\",\"file_2\",..."]"

// Vérifier avec:
debugClipboard()
// Output devrait montrer:
// "clipboard (var): [Array(5)]"
// "Items parsés (clipboard): 5 items"
```

### Étape 5: Tester Coller

```javascript
// Naviguer vers un autre dossier (optionnel)
// Cliquer sur "Coller"
// Dans la console, vous devriez voir:
// "Collage - Items à coller: [Array(5)]"
// "Collage - Payload envoyé: {items: Array(5), target_folder: "root", is_cut: false}"
// "Collage - Réponse du serveur (status): 200"

// La page devrait recharger automatiquement et afficher les 5 fichiers collés
```

### Étape 6: Vérifier les Logs Serveur

```bash
# Terminal:
tail -f /opt/lampp/logs/apache_php_error.log
# ou
tail -f /var/log/apache2/error.log

# Chercher:
# "=== PASTE.PHP DEBUG ==="
# "items count: 5"
# "items: [...énumération de 5 IDs...]"
```

---

## Commandes de Débogage Console

```javascript
// Afficher le state complet
debugClipboard()

// Vérifier sélection par vue
document.querySelectorAll('.details-view .file-checkbox:checked').length
document.querySelectorAll('.list-view .list-item.selected').length
document.querySelectorAll('.icons-view .icon-item.selected').length

// Vérifier localStorage
localStorage.getItem('lundrive_clipboard')
localStorage.getItem('lundrive_cut')

// Tester manuellement getAllSelectedItems()
getAllSelectedItems()

// Tester manuellement prepareCopy()
prepareCopy()
```

---

## Checklist de Résolution

- [ ] Console navigateur (F12) n'affiche aucune erreur JavaScript
- [ ] `debugClipboard()` affiche 5 items après sélection
- [ ] Les logs de copie et coupe affichent bien les 5 éléments
- [ ] Le payload JSON envoye bien 5 items (visible dans "Payload envoyé")
- [ ] Le serveur reçoit bien les 5 items (visible dans les logs)
- [ ] Après collage, 5 fichiers apparaissent dans le dossier
- [ ] Pas d'erreur 5xx du serveur (status devrait être 200)

---

## Corrections Appliquées

### Fichier `files.php`

1. **getAllSelectedItems()** - Amélioration:
   - Avant: cherchait les éléments sélectionnés dans TOUTES les vues
   - Après: cherche uniquement dans la vue active
   - Résultat: pas de doublons ou d'éléments faux

2. **updateSelectedCount()** - Amélioration:
   - Avant: cumulait le count de toutes les vues
   - Après: compte uniquement la vue active
   - Résultat: affichage correct du nombre d'éléments

3. **prepareCopy()** - Amélioration:
   - Avant: pas de logs
   - Après: logs détaillés et localStorage persiste
   - Résultat: données conservées même après rechargement

4. **prepareCut()** - Amélioration:
   - Avant: pas de logs
   - Après: logs détaillés et localStorage persiste
   - Résultat: déplacement fiable

5. **preparePaste()** - Amélioration:
   - Avant: pas de logs, erreurs masquées
   - Après: logs détaillés, gestion d'erreur améliorée
   - Résultat: rapport d'erreur clair si problème

### Fichier `paste.php`

1. **Logs de débogage** ajoutés:
   - Affiche les items reçus
   - Affiche le nombre d'items
   - Affiche la cible et le mode (cut vs copy)
   - Résultat: factile identifier les erreurs côté serveur

---

## Étapes Suivantes si Problème Persiste

### 1. Vérifier les Limites de Taille
```bash
# Vérifier limite POST PHP
grep "post_max_size" /opt/lampp/etc/php.ini
# Vérifier limite clientBody Nginx/Apache
```

### 2. Vérifier les Permissions de Dossier
```bash
ls -la /opt/lampp/htdocs/file_manager/uploads/
# Devrait avoir permissions 777 ou 755
```

### 3. Vérifier les Erreurs de Base de Données
```bash
# Vérifier les logs MySQL
tail -f /opt/lampp/var/mysql/error.log
```

---

## Rapport à Fournir si Support Nécessaire

Si le problème persiste après ce guide, fournir:

1. **Console navigateur** (capture de debugClipboard())
2. **Logs serveur** (tail du fichier d'erreur)
3. **Nombres exacts** des fichiers sélectionnés et collés
4. **Vue utilisée** (Détails, Liste, ou Icônes)
5. **Navigateur** et **version** (Firefox, Chrome, Safari, etc.)

