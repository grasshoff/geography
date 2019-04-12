repoBaseDir="/var/repositories"

for I in $(ls $repoBaseDir | awk -F/ ' length($NF)  <= 5 ');
do
	ln -s $repoBaseDir/$I .
done
